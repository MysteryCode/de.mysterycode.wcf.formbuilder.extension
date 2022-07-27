/**
 * @author Florian Gail
 * @module MysteryCode/Ui/Attachment/Upload
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Dom/Util", "WoltLabSuite/Core/Core", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/Event/Handler", "WoltLabSuite/Core/Image/ImageUtil", "WoltLabSuite/Core/Image/Resizer", "WoltLabSuite/Core/Dom/Change/Listener", "WoltLabSuite/Core/Dom/Change/Listener", "WoltLabSuite/Core/Upload", "WoltLabSuite/Core/Environment", "WoltLabSuite/Core/FileUtil"], function (require, exports, tslib_1, Ajax, DomUtil, Core, Language, EventHandler, ImageUtil, Resizer_1, ChangeListener, Listener_1, Upload_1, Environment, FileUtil) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.AttachmentUpload = void 0;
    Ajax = tslib_1.__importStar(Ajax);
    DomUtil = tslib_1.__importStar(DomUtil);
    Core = tslib_1.__importStar(Core);
    Language = tslib_1.__importStar(Language);
    EventHandler = tslib_1.__importStar(EventHandler);
    ImageUtil = tslib_1.__importStar(ImageUtil);
    Resizer_1 = tslib_1.__importDefault(Resizer_1);
    ChangeListener = tslib_1.__importStar(ChangeListener);
    Listener_1 = tslib_1.__importDefault(Listener_1);
    Upload_1 = tslib_1.__importDefault(Upload_1);
    Environment = tslib_1.__importStar(Environment);
    FileUtil = tslib_1.__importStar(FileUtil);
    class AttachmentUpload extends Upload_1.default {
        constructor(buttonContainerId, targetId, objectType, objectID, tmpHash, parentObjectID, maxUploads, editorId, options) {
            super(buttonContainerId, targetId, Core.extend({
                multiple: true,
                className: "wcf\\data\\attachment\\AttachmentAction",
                maxUploads: maxUploads,
                singleFileRequests: true,
                objectType: objectType,
            }, options));
            this._autoInsert = [];
            this._replaceOnLoad = {};
            this._resizer = null;
            this._objectID = objectID;
            this._tmpHash = tmpHash;
            this._parentObjectID = parentObjectID;
            this._editorId = editorId;
            this.checkMaxFiles();
            this._target.querySelectorAll(":scope > li:not(.uploadFailed)").forEach((attachment) => {
                this.registerEditorButtons(attachment);
            });
            EventHandler.add("com.woltlab.wcf.action.delete", "attachment", (data) => this.onDelete(data));
            this.makeSortable();
            // for backwards compatibility, the object is still created but only inserted
            // if an editor is used
            this._insertAllButton = document.createElement("p");
            this._insertAllButton.classList.add("button", "jsButtonAttachmentInsertAll");
            this._insertAllButton.textContent = Language.get("wcf.attachment.insertAll");
            DomUtil.hide(this._insertAllButton);
            if (this._editorId) {
                this._buttonContainer.appendChild(this._insertAllButton);
                this._insertAllButton.addEventListener("click", () => this.insertAll());
                if (this._target.querySelectorAll(":scope > li:not(.uploadFailed)").length) {
                    DomUtil.show(this._insertAllButton);
                }
                EventHandler.add("com.woltlab.wcf.redactor2", "submit_" + this._editorId, (data) => this.submitInline(data));
                EventHandler.add("com.woltlab.wcf.redactor2", "reset_" + this._editorId, () => this.reset());
                EventHandler.add("com.woltlab.wcf.redactor2", "dragAndDrop_" + this._editorId, (data) => this.editorUpload(data));
                EventHandler.add("com.woltlab.wcf.redactor2", "pasteFromClipboard_" + this._editorId, (data) => this.editorUpload(data));
                EventHandler.add("com.woltlab.wcf.redactor2", "autosaveMetaData_" + this._editorId, (data) => {
                    if (!data.tmpHashes || !Array.isArray(data.tmpHashes)) {
                        data.tmpHashes = [];
                    }
                    // Remove any existing entries for this tmpHash.
                    data.tmpHashes = data.tmpHashes.filter((item) => item !== tmpHash);
                    const count = this._target.querySelectorAll(":scope > li:not(.uploadFailed)").length;
                    if (count > 0) {
                        // Add a new entry for this tmpHash if files have been uploaded.
                        data.tmpHashes.push(tmpHash);
                    }
                });
                const form = this._target.closest("form");
                if (form !== null) {
                    // Read any cached `tmpHash` values from the autosave feature.
                    const metaData = {};
                    form.dataset.attachmentTmpHashes = "";
                    EventHandler.fire("com.woltlab.wcf.redactor2", "getMetaData_" + this._editorId, metaData);
                    if (metaData.tmpHashes && Array.isArray(metaData.tmpHashes) && metaData.tmpHashes.length > 0) {
                        // Caching the values here preserves them from the removal
                        // caused by the automated cleanup that runs on form submit
                        // and is bound before our event listener.
                        form.dataset.attachmentTmpHashes = metaData.tmpHashes.join(",");
                    }
                    form.addEventListener("submit", () => {
                        let tmpHash = this._tmpHash;
                        if (form.dataset.attachmentTmpHashes) {
                            tmpHash += `,${form.dataset.attachmentTmpHashes}`;
                        }
                        const input = form.querySelector('input[name="tmpHash"]');
                        if (input !== null) {
                            input.value = tmpHash;
                        }
                    });
                }
                const metacodeAttachUuid = EventHandler.add("com.woltlab.wcf.redactor2", "metacode_attach_" + this._editorId, (data) => {
                    const images = this.getImageAttachments();
                    const attachmentId = data.attributes[0] || 0;
                    if (Object.hasOwnProperty.call(images, attachmentId)) {
                        const thumbnailWidth = ~~$("#" + this._editorId).data("redactor").opts.woltlab.attachmentThumbnailWidth;
                        let thumbnail = data.attributes[2];
                        thumbnail = thumbnail === true || thumbnail === "true" || (~~thumbnail && ~~thumbnail <= thumbnailWidth);
                        const image = document.createElement("img");
                        image.className = "woltlabAttachment";
                        image.src = images[attachmentId][thumbnail ? "thumbnailUrl" : "url"];
                        image.dataset.attachmentId = attachmentId;
                        const float = data.attributes[1] || "none";
                        if (float === "left") {
                            image.classList.add("messageFloatObjectLeft");
                        }
                        else if (float === "right") {
                            image.classList.add("messageFloatObjectRight");
                        }
                        const metacode = data.metacode;
                        metacode.parentNode.insertBefore(image, metacode);
                        metacode.remove();
                        data.cancel = true;
                    }
                });
                const syncUuid = EventHandler.add("com.woltlab.wcf.redactor2", "sync_" + this._tmpHash, (data) => this.sync(data));
                EventHandler.add("com.woltlab.wcf.redactor2", "destroy_" + this._editorId, () => {
                    EventHandler.removeAll("com.woltlab.wcf.redactor2", "submit_" + this._editorId);
                    EventHandler.removeAll("com.woltlab.wcf.redactor2", "reset_" + this._editorId);
                    EventHandler.removeAll("com.woltlab.wcf.redactor2", "insertAttachment_" + this._editorId);
                    EventHandler.removeAll("com.woltlab.wcf.redactor2", "dragAndDrop_" + this._editorId);
                    EventHandler.removeAll("com.woltlab.wcf.redactor2", "pasteFromClipboard_" + this._editorId);
                    EventHandler.removeAll("com.woltlab.wcf.redactor2", "autosaveMetaData_" + this._editorId);
                    EventHandler.remove("com.woltlab.wcf.redactor2", "metacode_attach_" + this._editorId, metacodeAttachUuid);
                    EventHandler.remove("com.woltlab.wcf.redactor2", "sync_" + this._tmpHash, syncUuid);
                });
            }
        }
        _createButton() {
            this._fileUpload = document.createElement("input");
            this._fileUpload.type = "file";
            this._fileUpload.name = this._options.name;
            if (this._options.multiple) {
                this._fileUpload.multiple = true;
            }
            if (this._options.acceptableFiles !== null) {
                this._fileUpload.accept = this._options.acceptableFiles.join(",");
            }
            this._fileUpload.addEventListener("change", (ev) => this.initUpload(ev));
            this._button = document.createElement("p");
            this._button.className = "button uploadButton";
            this._button.setAttribute("role", "button");
            this._fileUpload.addEventListener("focus", () => {
                if (this._fileUpload.classList.contains("focus-visible")) {
                    this._button.classList.add("active");
                }
            });
            this._fileUpload.addEventListener("blur", () => {
                this._button.classList.remove("active");
            });
            const span = document.createElement("span");
            span.textContent = Language.get("wcf.global.button.upload");
            this._button.appendChild(span);
            this._button.insertAdjacentElement("afterbegin", this._fileUpload);
            this._insertButton();
            Listener_1.default.trigger();
        }
        resize(file, blob) {
            const item = blob ? blob : file;
            // Ignore anything that is not a widely used mimetype for static images.
            // GIFs are not supported due to the support for animations.
            if (["image/png", "image/jpeg", "image/webp"].indexOf(item.type) === -1) {
                if (blob) {
                    return Promise.resolve(this._upload(null, null, blob));
                }
                return Promise.resolve(this._upload(null, file));
            }
            if (blob) {
                file = FileUtil.blobToFile(blob, "pasted-from-clipboard");
            }
            const maxSize = parseInt(this._buttonContainer.dataset.maxSize || "");
            const resizer = new Resizer_1.default();
            const timeout = new Promise((resolve) => {
                // We issue one timeout per image, thus multiple timeout
                // handlers will run in parallel
                setTimeout(() => {
                    resolve(file);
                }, 10000);
            });
            return resizer
                .loadFile(file)
                .then((result) => {
                const exif = result.exif;
                let maxWidth = this._options.autoScale.maxWidth;
                const maxHeight = this._options.autoScale.maxHeight;
                const quality = this._options.autoScale.quality;
                if (window.devicePixelRatio >= 2) {
                    const realWidth = window.screen.width * window.devicePixelRatio;
                    const realHeight = window.screen.height * window.devicePixelRatio;
                    // Check whether the width of the image is roughly the width of the physical screen, and
                    // the height of the image is at least the height of the physical screen.
                    if (realWidth - 10 < result.image.width &&
                        result.image.width < realWidth + 10 &&
                        realHeight - 10 < result.image.height) {
                        // This appears to be a screenshot from a HiDPI device in portrait mode: Scale to logical size
                        maxWidth = Math.min(maxWidth, window.screen.width);
                    }
                }
                return resizer
                    .resize(result.image, maxWidth, maxHeight, quality, file.size > maxSize, timeout)
                    .then((resizedImage) => {
                    // Check whether the image actually was resized
                    if (resizedImage === undefined) {
                        return file;
                    }
                    let fileType = this._options.autoScale.fileType;
                    if (this._options.autoScale.fileType === "keep" || ImageUtil.containsTransparentPixels(resizedImage)) {
                        fileType = file.type;
                    }
                    return resizer.saveFile({
                        exif: exif,
                        image: resizedImage,
                    }, file.name, fileType, quality);
                })
                    .then((resizedFile) => {
                    if (resizedFile.size > file.size) {
                        console.debug(`[MysteryCode/Ui/Attachment/Upload] File size of "${file.name}" increased, uploading untouched image.`);
                        return file;
                    }
                    return resizedFile;
                });
            })
                .catch((error) => {
                console.debug(`[MysteryCode/Ui/Attachment/Upload] Failed to resize image "${file.name}":`, error);
                return file;
            })
                .then((file) => {
                return this._upload(null, file);
            });
        }
        initUpload(event) {
            const files = Array.from(this._fileUpload.files);
            if (this._options.autoScale && this._options.autoScale.enable) {
                files.forEach((file) => {
                    void this.resize(file);
                });
            }
            else {
                this._upload(event);
            }
        }
        _getParameters() {
            return {
                objectType: this._options.objectType,
                objectID: this._objectID,
                tmpHash: this._tmpHash,
                parentObjectID: this._parentObjectID,
            };
        }
        checkMaxFiles() {
            if (this._options.maxUploads !== null && this._target.childElementCount >= this._options.maxUploads) {
                DomUtil.hide(this._button);
            }
            else {
                DomUtil.show(this._button);
            }
        }
        insert(event) {
            EventHandler.fire("com.woltlab.wcf.redactor2", "insertAttachment_" + this._editorId, {
                attachmentId: event.currentTarget.dataset.objectId || 0,
                url: event.currentTarget.dataset.url || "",
            });
        }
        insertAll() {
            let attachment;
            let button;
            for (let i = 0, length = this._target.childNodes.length; i < length; i++) {
                attachment = this._target.childNodes[i];
                if (attachment.nodeName === "LI" && !attachment.classList.contains("uploadFailed")) {
                    button = attachment.querySelector(".jsButtonAttachmentInsertThumbnail, .jsButtonAttachmentInsertPlain");
                    if (button === null) {
                        button = attachment.querySelector(".jsButtonAttachmentInsertFull, .jsButtonAttachmentInsertPlain");
                    }
                    button === null || button === void 0 ? void 0 : button.dispatchEvent(new MouseEvent("click"));
                }
            }
        }
        onDelete(data) {
            const objectId = parseInt(data.button.dataset.objectId || "", 10);
            const attachment = this._target.querySelector(`.formAttachmentListItem[data-object-id="${objectId}"]`);
            if (attachment !== null) {
                attachment.remove();
            }
        }
        makeSortable() {
            let attachmentsFound = false;
            this._target.querySelectorAll(":scope > li:not(.uploadFailed)").forEach((attachment) => {
                attachmentsFound = true;
                if (!attachment.classList.contains("sortableAttachment")) {
                    attachment.classList.add("sortableAttachment");
                }
                const img = attachment.querySelector("img");
                if (img !== null && !img.classList.contains("sortableNode")) {
                    img.classList.add("sortableNode");
                }
            });
            if (attachmentsFound && !this._target.classList.contains("sortableList")) {
                this._target.classList.add("sortableList");
                if (Environment.platform() === "desktop") {
                    new window.WCF.Sortable.List(DomUtil.identify(this._target.parentNode), "", 0, {
                        axis: false,
                        items: "li.sortableAttachment",
                        toleranceElement: null,
                        start: (event, ui) => {
                            const offsetHeight = ui.helper[0].offsetHeight;
                            ui.placeholder[0].style.setProperty("height", `${offsetHeight}px`, "");
                        },
                        update: () => {
                            const attachmentIDs = [];
                            this._target.querySelectorAll(":scope > li:not(.uploadFailed)").forEach((listItem) => {
                                if (listItem.dataset.objectId) {
                                    attachmentIDs.push(parseInt(listItem.dataset.objectId, 10));
                                }
                            });
                            if (attachmentIDs.length) {
                                Ajax.apiOnce({
                                    data: {
                                        actionName: "updatePosition",
                                        className: "wcf\\data\\attachment\\AttachmentAction",
                                        parameters: {
                                            attachmentIDs: attachmentIDs,
                                            objectID: this._objectID,
                                            objectType: this._options.objectType,
                                            tmpHash: this._tmpHash,
                                        },
                                    },
                                });
                            }
                        },
                    }, true);
                }
            }
        }
        getImageAttachments() {
            const images = {};
            this._target.querySelectorAll(":scope > li").forEach((element) => {
                if (element.dataset.isImage) {
                    images[~~(element.dataset.objectId || 0)] = {
                        thumbnailUrl: element.querySelector(".jsButtonAttachmentInsertThumbnail").dataset.url || null,
                        url: element.querySelector(".jsButtonAttachmentInsertFull").dataset.url,
                    };
                }
            });
            return images;
        }
        submitInline(data) {
            if (this._tmpHash) {
                data.tmpHash = this._tmpHash;
                const metaData = {};
                EventHandler.fire("com.woltlab.wcf.redactor2", "getMetaData_" + this._editorId, metaData);
                if (metaData.tmpHashes && Array.isArray(metaData.tmpHashes) && metaData.tmpHashes.length > 0) {
                    data.tmpHash += "," + metaData.tmpHashes.join(",");
                }
            }
        }
        reset() {
            this._target.childNodes.forEach((element) => {
                element.remove();
                DomUtil.hide(this._target);
            });
            if (this._insertAllButton !== null) {
                DomUtil.hide(this._insertAllButton);
            }
            this.checkMaxFiles();
        }
        editorUpload(data) {
            // show tab
            // TODO stupid jQuery
            $(this._target.closest(".messageTabMenu")).messageTabMenu("showTab", "attachments", true);
            const item = data.file ? data.file : data.blob;
            let promise;
            if (this._options.autoScale &&
                this._options.autoScale.enable &&
                ["image/png", "image/jpeg", "image/webp"].indexOf(item.type) !== -1) {
                if (data.blob) {
                    promise = this.resize(null, data.blob);
                }
                else {
                    promise = this.resize(data.file);
                }
            }
            else if (data.file) {
                promise = Promise.resolve(this._upload(null, data.file));
            }
            else {
                promise = Promise.resolve(this._upload(null, null, data.blob));
            }
            void promise.then((uploadId) => {
                data.uploadID = uploadId;
                if (Array.isArray(uploadId) && uploadId.length === 1) {
                    uploadId = uploadId.pop();
                }
                if (typeof uploadId === "number") {
                    if (!Array.isArray(uploadId)) {
                        if (!data.file && data.replace) {
                            this._replaceOnLoad[uploadId] = data.replace;
                        }
                        else {
                            this._autoInsert.push(uploadId);
                        }
                    }
                }
            });
        }
        sync(payload) {
            if (payload.source === this) {
                return;
            }
            switch (payload.type) {
                case "new":
                    this.syncNew(payload.data);
                    break;
                default:
                    throw new Error(`Unexpected type '${payload.type}'`);
            }
        }
        syncNew(data) {
            const fragment = DomUtil.createFragmentFromHtml(data.html);
            const attachment = fragment.querySelector(":scope > li");
            attachment.id = "";
            this.registerEditorButtons(attachment);
            this._target.appendChild(attachment);
        }
        rebuildInterface() {
            this.makeSortable();
            if (this._insertAllButton !== null) {
                if (this._target.querySelector(":scope > li:not(.uploadFailed)") !== null) {
                    DomUtil.show(this._insertAllButton);
                }
                else {
                    DomUtil.hide(this._insertAllButton);
                }
            }
            ChangeListener.trigger();
        }
        registerEditorButtons(attachment) {
            if (this._editorId) {
                attachment
                    .querySelectorAll(".jsButtonAttachmentInsertThumbnail, .jsButtonAttachmentInsertFull, .jsButtonAttachmentInsertPlain")
                    .forEach((button) => {
                    button.addEventListener("click", (event) => this.insert(event));
                });
            }
        }
        validateUpload(files) {
            if (this._options.maxUploads === null ||
                files.length + this._target.childElementCount <= this._options.maxUploads) {
                return true;
            }
            const parent = this._buttonContainer.parentElement;
            let innerError = parent.querySelector("small.innerError:not(.innerFileError)");
            if (innerError === null) {
                innerError = document.createElement("small");
                innerError.className = "innerError";
                this._buttonContainer.insertAdjacentElement("afterend", innerError);
            }
            innerError.textContent = Language.get("wcf.upload.error.reachedRemainingLimit", {
                maxFiles: this._options.maxUploads - this._target.childElementCount,
            });
            return false;
        }
        _upload(event, file, blob) {
            const parent = this._buttonContainer.parentElement;
            const innerError = parent.querySelector("small.innerError:not(.innerFileError)");
            if (innerError) {
                innerError.remove();
            }
            return super._upload(event, file, blob);
        }
        _createFileElement(file) {
            DomUtil.show(this._target);
            const element = super._createFileElement(file);
            element.classList.add("box64", "jsObjectActionObject");
            const progress = element.querySelector("progress");
            const icon = document.createElement("span");
            icon.className = "icon icon64 fa-spinner";
            const fileName = element.textContent;
            element.textContent = "";
            element.append(icon);
            const innerDiv = document.createElement("div");
            const fileNameP = document.createElement("p");
            fileNameP.textContent = fileName;
            const smallProgress = document.createElement("small");
            smallProgress.appendChild(progress);
            innerDiv.appendChild(fileNameP);
            innerDiv.appendChild(smallProgress);
            const div = document.createElement("div");
            div.appendChild(innerDiv);
            const ul = document.createElement("ul");
            ul.className = "buttonGroup";
            div.appendChild(ul);
            // reset element textContent and replace with own element style
            element.append(div);
            return element;
        }
        _failure(uploadId, data) {
            this._fileElements[uploadId].forEach((fileElement) => {
                fileElement.classList.add("uploadFailed");
                const small = fileElement.querySelector("small");
                small.innerHTML = "";
                const icon = fileElement.querySelector(".icon");
                icon.classList.remove("fa-spinner");
                icon.classList.add("fa-ban");
                const innerError = document.createElement("span");
                innerError.className = "innerError";
                innerError.textContent = Language.get("wcf.upload.error.uploadFailed");
                small.insertAdjacentElement("afterend", innerError);
            });
            throw new Error(`Upload failed: ${data.message}`);
        }
        _success(uploadId, data) {
            this._fileElements[uploadId].forEach((fileElement) => {
                if (data.returnValues.attachments[uploadId] !== undefined) {
                    const fileData = data.returnValues.attachments[uploadId];
                    fileElement.dataset.objectId = fileData.attachmentID.toString();
                    fileElement.querySelector("small").textContent = fileData.formattedFilesize;
                    const link = document.createElement("a");
                    link.href = fileData.url;
                    link.target = "_blank";
                    link.textContent = fileData.filename;
                    const icon = fileElement.querySelector(".icon");
                    if (fileData.isImage) {
                        const img = document.createElement("img");
                        img.src = fileData.tinyURL ? fileData.tinyURL : fileData.url;
                        img.alt = "";
                        img.classList.add("attachmentTinyThumbnail");
                        icon.replaceWith(img);
                        fileElement.dataset.height = fileData.height.toString();
                        fileElement.dataset.width = fileData.width.toString();
                        fileElement.dataset.isImage = fileData.isImage ? "1" : "0";
                        link.classList.add("jsImageViewer");
                        link.title = fileData.filename;
                    }
                    else {
                        icon.classList.remove("fa-spinner");
                        icon.classList.add(`fa-${fileData.iconName}`);
                    }
                    const p = fileElement.querySelector("p");
                    p.textContent = "";
                    p.appendChild(link);
                    const buttonGroup = fileElement.querySelector(".buttonGroup");
                    let li = document.createElement("li");
                    const deleteButton = document.createElement("span");
                    deleteButton.classList.add("button", "small", "jsObjectAction");
                    deleteButton.dataset.objectAction = "delete";
                    deleteButton.dataset.confirmMessage = Language.get("wcf.attachment.delete.sure");
                    deleteButton.dataset.eventName = "attachment";
                    deleteButton.textContent = Language.get("wcf.global.button.delete");
                    li.appendChild(deleteButton);
                    buttonGroup.appendChild(li);
                    if (this._editorId) {
                        if (fileData.tinyURL) {
                            if (fileData.thumbnailURL) {
                                li = document.createElement("li");
                                const insertThumbnailButton = document.createElement("span");
                                insertThumbnailButton.classList.add("button", "small", "jsButtonAttachmentInsertThumbnail");
                                insertThumbnailButton.dataset.objectId = fileData.attachmentID.toString();
                                insertThumbnailButton.dataset.url = fileData.thumbnailURL;
                                insertThumbnailButton.textContent = Language.get("wcf.attachment.insertThumbnail");
                                li.appendChild(insertThumbnailButton);
                                buttonGroup.appendChild(li);
                            }
                            li = document.createElement("li");
                            const insertImageButton = document.createElement("span");
                            insertImageButton.classList.add("button", "small", "jsButtonAttachmentInsertFull");
                            insertImageButton.dataset.objectId = fileData.attachmentID.toString();
                            insertImageButton.dataset.url = fileData.url;
                            insertImageButton.textContent = Language.get("wcf.attachment.insertFull");
                            li.appendChild(insertImageButton);
                            buttonGroup.appendChild(li);
                        }
                        else {
                            li = document.createElement("li");
                            const insertButton = document.createElement("span");
                            insertButton.classList.add("button", "small", "jsButtonAttachmentInsertPlain");
                            insertButton.dataset.objectId = fileData.attachmentID.toString();
                            insertButton.textContent = Language.get("wcf.attachment.insert");
                            li.appendChild(insertButton);
                            buttonGroup.appendChild(li);
                        }
                    }
                    this.triggerSync("new", {
                        html: fileElement.outerHTML,
                    });
                    this.registerEditorButtons(fileElement);
                    if (Object.hasOwnProperty.call(this._replaceOnLoad, uploadId)) {
                        if (!fileElement.classList.contains("uploadFailed")) {
                            const img = this._replaceOnLoad[uploadId];
                            if (img && img.parentNode) {
                                EventHandler.fire("com.woltlab.wcf.redactor2", "replaceAttachment_" + this._editorId, {
                                    attachmentId: fileData.attachmentID,
                                    img: img,
                                    src: fileData.thumbnailURL ? fileData.thumbnailURL : fileData.url,
                                });
                            }
                        }
                        this._replaceOnLoad[uploadId] = null;
                    }
                }
                else if (data.returnValues.errors[uploadId] !== undefined) {
                    const errorData = data.returnValues["errors"][uploadId];
                    fileElement.classList.add("uploadFailed");
                    const small = fileElement.querySelector("small");
                    small.innerHTML = "";
                    const icon = fileElement.querySelector(".icon");
                    icon.classList.remove("fa-spinner");
                    icon.classList.add("fa-ban");
                    let innerError = fileElement.querySelector(".innerError");
                    if (innerError === null) {
                        innerError = document.createElement("span");
                        innerError.className = "innerError";
                        innerError.textContent = errorData.errorType;
                        small.insertAdjacentElement("afterend", innerError);
                    }
                    else {
                        innerError.textContent = errorData.errorType;
                    }
                }
                else {
                    throw new Error(`Unknown uploaded file for uploadId ${uploadId}.`);
                }
                if (this._autoInsert.includes(uploadId)) {
                    this._autoInsert.splice(this._autoInsert.indexOf(uploadId), 1);
                    if (!fileElement.classList.contains("uploadFailed")) {
                        let button = fileElement.querySelector(".jsButtonAttachmentInsertThumbnail");
                        if (button === null) {
                            button = fileElement.querySelector(".jsButtonAttachmentInsertFull");
                        }
                        if (button !== null) {
                            button.dispatchEvent(new MouseEvent("click"));
                        }
                    }
                }
            });
            // create delete buttons
            this.checkMaxFiles();
            this.makeSortable();
            Core.triggerEvent(this._target, "change");
        }
        triggerSync(type, data) {
            EventHandler.fire("com.woltlab.wcf.redactor2", "sync_" + this._tmpHash, {
                source: this,
                type: type,
                data: data,
            });
        }
    }
    exports.AttachmentUpload = AttachmentUpload;
    exports.default = AttachmentUpload;
});

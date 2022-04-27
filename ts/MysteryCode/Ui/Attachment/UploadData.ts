/**
 * @author      Florian Gail
 * @module      MysteryCode/Ui/Attachment/Upload/Data
 */

import {UploadId, UploadOptions} from "WoltLabSuite/Core/Upload/Data";
import {DatabaseObjectActionResponse} from "WoltLabSuite/Core/Ajax/Data";

interface IAutoScale {
	enable: boolean;
	fileType: string;
	maxWidth: number;
	maxHeight: number;
	quality: number;
}

export interface AttachmentUploadOptions extends UploadOptions {
	maxUploads: number;
	autoScale?: IAutoScale;
	objectType: string;
}

interface AttachmentData {
	filename: string;
	filesize: number;
	formattedFilesize: string;
	isImage: boolean;
	attachmentID: number;
	tinyURL: string;
	thumbnailURL: string;
	url: string;
	height: number;
	width: number;
	iconName: string;
}

interface ErrorData {
	filename: string;
	filesize: number;
	errorType: string;
	additionalData: string[];
}

export interface AjaxResponseReturnValues {
	errors: ErrorData[];
	attachments: AttachmentData[];
}

export interface AjaxResponse extends DatabaseObjectActionResponse {
	returnValues: AjaxResponseReturnValues;
}

interface ImageAttachment {
	thumbnailUrl: string | null;
	url: string;
}

export interface ImageAttachments {
	[key: string]: ImageAttachment;
}

export interface IEditorUploadData {
	file: File;
	blob: Blob;
	uploadID?: UploadId;
	replace?: HTMLImageElement | null;
}

export interface ISyncItemData {
	html: string;
}

export interface ISyncPayload {
	data: ISyncItemData;
	source: object;
	type: string;
}

export interface IDeleteData {
	button: HTMLElement;
	container: HTMLElement;
}

export interface ISubmitInlineData {
	tmpHash: string;
}

export interface IMetaData {
	tmpHashes?: Array<string> | null;
}

export interface ISortableListUi {
	helper: HTMLElement;
	item: HTMLElement;
	offset: number;
	position: object;
	originalPosition: object;
	sender: HTMLElement;
	placeholder: HTMLElement;
}

export interface IReplaceOnLoad {
	[key: number]: HTMLImageElement | null;
}

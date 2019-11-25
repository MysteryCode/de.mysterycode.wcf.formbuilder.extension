# de.mysterycode.wcf.formBuilder.extension

# Nested form fields
Sometimes you want to store some data serialzed as an array but you want to have a form field for every property.

The form builder introduced in WSC 5.2 is currently not able to handle this case properly, so there is some sort of workaround required. 

Usage example:
```PHP
use wcf\system\form\builder\container\DummyFormContainer;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\data\processor\PrefixedFormDataProcessor;
use wcf\system\form\builder\NestedFormDocument;
use wcf\system\form\builder\field\TextFormField;

$this->form = NestedFormDocument::create('DummyAdd');
$this->form->appendChildren([
	DummyFormContainer::create('additionalData')
        ->appendChildren([
            FormContainer::create('foo')
                ->label('wcf.dummy.foo')
                ->appendChildren([
                    TextFormField::create('additionalData[foo][bar]')
                        ->label('wcf.dummy.foo.bar')
                ])
        ])
    ]);
$this->form->getDataHandler()->addProcessor(new PrefixedFormDataProcessor('additionalData', 'additionalData'));
```

###Attention:
You have to serialize the array manually before writing it to your database!
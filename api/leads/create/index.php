<?php

//AmoCRM
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NullTagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\BirthdayCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;

//валидатор Valitron
use Valitron\Validator;

//вывод ответа
require_once __DIR__ . '/../../outputResponse.php';

/**
 * @return $accessToken
 */
require_once __DIR__ . '/../../credentials.php';

///проверка данных, полученных из формы
$v = new Validator($_POST);
$v->rules([
    'required' => [  
        'email', 
        'price',
        'telephone',
        'firstName'
    ],
    'regex'    => [ 
        [ 'email', '/@.+\./' ],
        [ 'price', '/^\+?\d+$/' ],
        [ 'telephone', '/^(\+?7|8)?9\d{9}$/']
    ],
    'lengthMin' => [ 
        [ 'firstName', '1']
    ],
    'lengthMax' => [ 
        [ 'firstName', '100'] 
    ]
]);

if (!($v->validate())) {
    outputResponse(false, $v->errors());
};

//данные из формы
$lead_price = $_POST["price"];
$leadContact_email = $_POST["email"];
$leadContact_firstName = $_POST["firstName"];
$leadContact_telephone = $_POST["telephone"];


///AmoCRM
//создание контакта AmoCRM
$contact = new ContactModel();
$contact->setName('Контакт');

//создание и заполнение полей контакта (email и телефон)
$customFieldsValues = new CustomFieldsValuesCollection();

$contactValues = [
    ['EMAIL', $leadContact_email], 
    ['PHONE', $leadContact_telephone]
];
foreach ($contactValues as list($enum, $value)) {
    $field = (new MultitextCustomFieldValuesModel())->setFieldCode($enum);
    $field->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setEnum('WORK')
                        ->setValue($value)
                )
    );
    $customFieldsValues->add($field);
};

$contact->setCustomFieldsValues($customFieldsValues);

//сохранение контакта
try {
    $contact = $apiClient->contacts()->addOne($contact); 
} catch (AmoCRMApiException $e) {
    outputResponse(false, $e);
};

//создание сделки с ценой
$leadsService = $apiClient->leads();
$lead = new LeadModel();

$lead->setName('Тестовая сделка')
    ->setPrice($lead_price);
$lead = $apiClient->leads()->addOne($lead);

//получение ID созданной сделки
try {
    $leadID = $lead->getId();
    $lead = $apiClient->leads()->getOne($leadID);
} catch (AmoCRMApiException $e) {
    outputResponse(false, $e);
};

//привязка контакта к сделке
$links = new LinksCollection();
$links->add($lead);
try {
    $apiClient->contacts()->link($contact, $links);
} catch (AmoCRMApiException $e) {
    outputResponse(false, $e);
};

//вывод ID сделки
outputResponse(true, $leadID);
?>
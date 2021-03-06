<?php

class ContactForm extends Form
{
    /**
     * Current page
     *
     * @var int
     */
    protected $page;

    /**
     * ContactForm constructor.
     *
     * @param \Controller $controller
     * @param string      $name
     */
    public function __construct(\Controller $controller, $name)
    {
        parent::__construct($controller, $name, FieldList::create(), FieldList::create(), null);

        $this->fields    = $this->getFieldList();
        $this->actions   = $this->getActions();
        $this->validator = $this->getRequiredFields();

        // setup form errors (re-init after parent::__construct call)
        parent::setupFormErrors();

        // trigger foundation abide validation
        $this->setAttribute('data-abide', 'data-abide');

        // add som emore page/controller requirement
        Requirements::javascript(JSEND_DIR . '/js/jsend.js');
    }

    /**
     * Retrieve the fieldlist
     *
     * @return FieldList
     */
    public function getFieldList()
    {
        $fieldlist = CompositeField::create($this->getBareFieldList());

        // Build actual form layout
        $row = CompositeField::create([
            $fieldlist,
        ])
            ->addExtraClass('row');

        return (new FieldList([
            $row,
        ]))->setForm($this);
    }

    /**
     * Retrieve the bare fieldlist
     *
     * @return array
     */
    public function getBareFieldList()
    {
        return [
            TextField::create(
                'Name',
                _t('ContactSubmission.Name', 'Name*')
            )
                ->setFieldHolderTemplate('FormField_holder'),
            TextField::create(
                'Email',
                _t('ContactSubmission.Email', 'E-mail*')
            )
                ->setFieldHolderTemplate('FormField_holder'),
            TextField::create(
                'Subject',
                _t('ContactSubmission.Subject', 'Subject*')
            )
                ->setFieldHolderTemplate('FormField_holder'),
            TextareaField::create(
                'Message',
                _t('ContactSubmission.Message', 'Message*')
            )
                ->setRows(10)
                ->setFieldHolderTemplate('FormField_holder'),
        ];
    }

    /**
     * Retrieve the actions
     *
     * @return FieldList
     */
    public function getActions()
    {
        return (new FieldList($this->getBareActions()))->setForm($this);
    }

    /**
     * Retrieve the bare actions
     *
     * @return array
     */
    public function getBareActions()
    {
        return [
            FormAction::create(
                'SendContactForm',
                $this->controller->data()->SubmitButtonText
            )
                ->addExtraClass('button tiny right ContactformAction')
                ->setUseButtonTag(true),
        ];
    }

    /**
     * Retrieve the required fields
     *
     * @return RequiredFields
     */
    public function getRequiredFields()
    {
        return (new RequiredFields($this->getBareRequiredFields()))->setForm($this);
    }

    /**
     * Retrieve the bare required fields
     *
     * @return array
     */
    public function getBareRequiredFields()
    {
        return [
            'Name',
            'Email',
            'Subject',
            'Message',
        ];
    }

    /**
     * Handle the contact form
     *
     * @param array $data
     * @param Form  $form
     */
    public function SendContactForm($data, $form)
    {
        //Save and email submission
        $submission = new ContactSubmission();
        $form->saveInto($submission);
        $submission->PageID = $this->controller->data()->ID;
        $submission->write();

        //Set data
        $From    = $data['Email'];
        $Sender  = sprintf('%1$s <%2$s>', $this->controller->data()->NameFrom, $this->controller->data()->MailFrom);
        $To      = $this->controller->data()->MailTo;
        $Subject = sprintf('%1$s: %2$s', $this->controller->data()->MailSubject, $data['Subject']);
        $email   = new Email($Sender, $To, $Subject);

        // Set reply-to naar persoon die het formulier heeft ingevuld
        $email->addCustomHeader('Reply-To', $From);

        //set template
        $email->setTemplate('ContactFormEmail');

        //populate template
        $email->populateTemplate([
            'ContactName'    => $data['Name'],
            'ContactEmail'   => $data['Email'],
            'ContactSubject' => $data['Subject'],
            'ContactMessage' => $data['Message'],
        ]);

        //send mail
        $email->send();

        //return to successpage
        $this->controller->redirect(Director::baseURL() . $this->controller->data()->URLSegment . "/?success=1");
    }
}
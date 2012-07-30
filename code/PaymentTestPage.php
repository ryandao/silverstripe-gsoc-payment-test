<?php

class PaymentTestPage extends Page {

  /**
   * TODO Could use to create default payment test page when /dev/build is run
   */
  function requireDefaultRecords() {
    parent::requireDefaultRecords();
  }
}

class PaymentTestPage_Controller extends Page_Controller {
  function index() {
    return array( 
       'Content' => $this->Content, 
       'Form' => $this->ProcessForm() 
    );
  }

  /**
   * Get the order form for processing a dummy payment
   */
  function ProcessForm() {
    $fields = new FieldList;

    // Create a dropdown select field for choosing gateway
    $supported_methods = PaymentProcessor::get_supported_methods();
    $source = array();
    foreach ($supported_methods as $methodName) {
      $methodConfig = PaymentFactory::get_factory_config($methodName);
      $source[$methodName] = $methodConfig['title'];
    }

    $fields->push(new DropDownField(
      'PaymentMethod', 
      'Select Payment Method', 
      $source
    ));

    $actions = new FieldList(
      new FormAction('proceed', 'Proceed')
    );
    
    return new Form($this, 'ProcessForm', $fields, $actions);
  }
  
  function proceed($data, $form) {
    Session::set('PaymentMethod', $data['PaymentMethod']);
    
    return $this->customise(array(
      'Content' => $this->Content,
      'Form' => $this->OrderForm()
    ))->renderWith('Page');
  }
  
  function OrderForm() {
    $paymentMethod = Session::get('PaymentMethod');
    $processor = PaymentFactory::factory($paymentMethod);
    $fields = $processor->getFormFields();
    $fields->push(new HiddenField('PaymentMethod', 'PaymentMethod', $paymentMethod));
    
    $actions = new FieldList(
      new FormAction('processOrder', 'Process Order')  
    );

    //$validator = $processor->getFormRequirements(); 
    //$validator = new RequiredFields('Amount', 'Currency');

    $validator = new RequiredFields();
    
    return new Form($this, 'OrderForm', $fields, $actions, $validator);
  }
  
  /**
   * Process order
   */
  function processOrder($data, $form) {

    SS_Log::log(new Exception(print_r($data, true)), SS_Log::NOTICE);

    $paymentMethod = $data['PaymentMethod'];
    print $paymentMethod;
    $paymentController = PaymentFactory::factory($paymentMethod);

    SS_Log::log(new Exception(print_r($paymentController, true)), SS_Log::NOTICE);

    return $paymentController->processRequest($data);
  }
}
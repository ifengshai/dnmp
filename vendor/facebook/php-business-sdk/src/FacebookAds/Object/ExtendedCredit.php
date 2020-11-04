<?php
/**
 * Copyright (c) 2015-present, Facebook, Inc. All rights reserved.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */

namespace FacebookAds\Object;

use FacebookAds\ApiRequest;
use FacebookAds\Cursor;
use FacebookAds\Http\RequestInterface;
use FacebookAds\TypeChecker;
use FacebookAds\Object\Fields\ExtendedCreditFields;
use FacebookAds\Object\Values\ExtendedCreditAllocationConfigLiabilityTypeValues;
use FacebookAds\Object\Values\ExtendedCreditAllocationConfigPartitionTypeValues;
use FacebookAds\Object\Values\ExtendedCreditAllocationConfigSendBillToValues;

/**
 * This class is auto-generated.
 *
 * For any issues or feature requests related to this class, please let us know
 * on github and we'll fix in our codegen framework. We'll not be able to accept
 * pull request for this class.
 *
 */

class ExtendedCredit extends AbstractCrudObject {

  /**
   * @return ExtendedCreditFields
   */
  public static function getFieldsEnum() {
    return ExtendedCreditFields::getInstance();
  }

  protected static function getReferencedEnums() {
    $ref_enums = array();
    return $ref_enums;
  }


  public function getExtendedCreditInvoiceGroups(array $fields = array(), array $params = array(), $pending = false) {
    $this->assureId();

    $param_types = array(
    );
    $enums = array(
    );

    $request = new ApiRequest(
      $this->api,
      $this->data['id'],
      RequestInterface::METHOD_GET,
      '/extended_credit_invoice_groups',
      new ExtendedCreditInvoiceGroup(),
      'EDGE',
      ExtendedCreditInvoiceGroup::getFieldsEnum()->getValues(),
      new TypeChecker($param_types, $enums)
    );
    $request->addParams($params);
    $request->addFields($fields);
    return $pending ? $request : $request->execute();
  }

  public function createExtendedCreditInvoiceGroup(array $fields = array(), array $params = array(), $pending = false) {
    $this->assureId();

    $param_types = array(
      'emails' => 'list<string>',
      'name' => 'string',
    );
    $enums = array(
    );

    $request = new ApiRequest(
      $this->api,
      $this->data['id'],
      RequestInterface::METHOD_POST,
      '/extended_credit_invoice_groups',
      new ExtendedCreditInvoiceGroup(),
      'EDGE',
      ExtendedCreditInvoiceGroup::getFieldsEnum()->getValues(),
      new TypeChecker($param_types, $enums)
    );
    $request->addParams($params);
    $request->addFields($fields);
    return $pending ? $request : $request->execute();
  }

  public function getOwningCreditAllocationConfigs(array $fields = array(), array $params = array(), $pending = false) {
    $this->assureId();

    $param_types = array(
      'receiving_business_id' => 'string',
    );
    $enums = array(
    );

    $request = new ApiRequest(
      $this->api,
      $this->data['id'],
      RequestInterface::METHOD_GET,
      '/owning_credit_allocation_configs',
      new ExtendedCreditAllocationConfig(),
      'EDGE',
      ExtendedCreditAllocationConfig::getFieldsEnum()->getValues(),
      new TypeChecker($param_types, $enums)
    );
    $request->addParams($params);
    $request->addFields($fields);
    return $pending ? $request : $request->execute();
  }

  public function createOwningCreditAllocationConfig(array $fields = array(), array $params = array(), $pending = false) {
    $this->assureId();

    $param_types = array(
      'amount' => 'Object',
      'liability_type' => 'liability_type_enum',
      'partition_type' => 'partition_type_enum',
      'receiving_business_id' => 'string',
      'send_bill_to' => 'send_bill_to_enum',
    );
    $enums = array(
      'liability_type_enum' => ExtendedCreditAllocationConfigLiabilityTypeValues::getInstance()->getValues(),
      'partition_type_enum' => ExtendedCreditAllocationConfigPartitionTypeValues::getInstance()->getValues(),
      'send_bill_to_enum' => ExtendedCreditAllocationConfigSendBillToValues::getInstance()->getValues(),
    );

    $request = new ApiRequest(
      $this->api,
      $this->data['id'],
      RequestInterface::METHOD_POST,
      '/owning_credit_allocation_configs',
      new ExtendedCreditAllocationConfig(),
      'EDGE',
      ExtendedCreditAllocationConfig::getFieldsEnum()->getValues(),
      new TypeChecker($param_types, $enums)
    );
    $request->addParams($params);
    $request->addFields($fields);
    return $pending ? $request : $request->execute();
  }

  public function getSelf(array $fields = array(), array $params = array(), $pending = false) {
    $this->assureId();

    $param_types = array(
    );
    $enums = array(
    );

    $request = new ApiRequest(
      $this->api,
      $this->data['id'],
      RequestInterface::METHOD_GET,
      '/',
      new ExtendedCredit(),
      'NODE',
      ExtendedCredit::getFieldsEnum()->getValues(),
      new TypeChecker($param_types, $enums)
    );
    $request->addParams($params);
    $request->addFields($fields);
    return $pending ? $request : $request->execute();
  }

}

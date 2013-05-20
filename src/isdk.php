<?php
##########################################################################
#########        Object Oriented PHP SDK for Infusionsoft        #########
#########           Created by Justin Morris on 09-10-08         #########
#########           Updated by Michael Fairchild on 01-07-13     #########
#########           Version 1.28.x    (Same as app version)      #########
##########################################################################

if (!function_exists('xmlrpc_encode_entitites')) {
  include('xmlrpc-3.0/lib/xmlrpc.inc');
}

class iSDK
{
  const VAL_ON    = 'on';
  const VAL_OFF   = 'off';
  const VAL_KILL  = 'kill';
  const VAL_THROW = 'throw';

  // Connect by using the Connection file or by passing in the variables
  public function cfgCon($name, $key = '', $dbOn = self::VAL_ON, $type = 'i')
  {
    $this->debug = (in_array($key, self::getKeyTypes()) ? $key : $dbOn);

    $this->key = $key;

    if (empty($key) || in_array($key, self::getKeyTypes())) {
      require_once 'conn.cfg.php';

      $appLines = $connInfo;

      foreach($appLines as $appLine){
        $details[substr($appLine, 0, strpos($appLine, ':'))] = explode(':',$appLine);
      }

      $appname   = $details[$name][1];
      $type      = $details[$name][2];
      $this->key = $details[$name][3];
    }

    switch ($type) {
      case 'm':
          $this->client = new xmlrpc_client('https://'. $appname .'.mortgageprocrm.com/api/xmlrpc');
        break;

      case 'i':
      default:
          if (!isset($appname)) {
            $appname = $name;
          }

          $this->client = new xmlrpc_client('https://'. $appname .'.infusionsoft.com/api/xmlrpc');
        break;
    }

    // Return Raw PHP Types
    $this->client->return_type = 'phpvals';

    // Don't bother with certificate verification
    $this->client->setSSLVerifyPeer(false);

    // Connection verification
    try{
      $isConnected = $this->dsGetSetting('Contact', 'optiontypes');
    }catch (Exception $e){
      throw new Exception('Connection Failed');
    }

    return true;
  }

  ###Connect and Obtain an API key from a vendor key###
  public function vendorCon($name, $user, $pass, $key = '', $dbOn = self::VAL_ON, $type = 'i')
  {
    $this->debug = (in_array($key, self::getKeyTypes()) ? $key : $dbOn);

    if($key != "" && $key != "on" && $key != "off" && $key != 'kill' && $key != 'throw') {
      if($type=="i") {
        $this->client = new xmlrpc_client("https://$name.infusionsoft.com/api/xmlrpc");
      } else if($type=="m") {
        $this->client = new xmlrpc_client("https://$name.mortgageprocrm.com/api/xmlrpc");
      } else {
        throw new Exception ("Invalid application type: \"$name\"");
      }
      $this->key = $key;
    }else{
      include('conn.cfg.php');
      $appLines = $connInfo;
      foreach($appLines as $appLine){
        $details[substr($appLine,0,strpos($appLine,":"))] = explode(":",$appLine);
      }
      if (!empty($details[$name])) {
        if ($details[$name][2]=="i") {
          $this->client = new xmlrpc_client("https://" . $details[$name][1] .
    ".infusionsoft.com/api/xmlrpc");
        } elseif ($details[$name][2]=="m") {
          $this->client = new xmlrpc_client("https://" . $details[$name][1] .
    ".mortgageprocrm.com/api/xmlrpc");
        } else {
            throw new Exception("Invalid application name: \"" . $name . "\"");
        }
      } else {
        throw new Exception("Application Does Not Exist: \"" . $name . "\"");
      }
      $this->key = $details[$name][3];
    }


    ###Return Raw PHP Types###
    $this->client->return_type = "phpvals";

    ###Dont bother with certificate verification###
    $this->client->setSSLVerifyPeer(FALSE);

    $carray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($user),
      php_xmlrpc_encode(md5($pass)));

    $this->key = $this->methodCaller("DataService.getTemporaryKey",$carray);

    try{
      $connected = $this->dsGetSetting("Contact","optiontypes");
    }catch (Exception $e){
      throw new Exception("Connection Failed");
    }
      return TRUE;
  }

  /**
   * Function to check
   *
   * @param string $text Text to echo
   * @return string
   */
  public function appEcho($text)
  {
    $cArray = array(
      php_xmlrpc_encode($text)
    );

    return $this->methodCaller('DataService.echo', $cArray);
  }

  ###Method Caller###
  public function methodCaller($service, $callArray)
  {
    $call = new xmlrpcmsg($service, $callArray);

    $result = $this->client->send($call);

    // Is everything goes well or not?
    if(!$result->faultCode()) {
      return $result->value();
    }

    switch ($this->debug) {
      case self::VAL_KILL:
          die("ERROR: " . $result->faultCode() . " - " . $result->faultString());
        break;
      case self::VAL_ON:
          return "ERROR: " . $result->faultCode() . " - ". $result->faultString();
        break;
      case self::VAL_THROW:
          throw new iSDKException($result->faultString(), $result->faultCode());
        break;
      case self::VAL_OFF:
      default:
        break;
    }
  }

  /**
   * FILE SERVICE
   */
  //String getFile(String key, int fileId) - returns base64 encoded file data
  public function getFile($fileID) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$fileID));
      $result = $this->methodCaller("FileService.getFile",$carray);
      return $result;
  }

  //int uploadFile(String key, String fileName, String base64encoded) - returns file id
  public function uploadFile($fileName,$base64Enc,$cid=0) {
      $result = 0;
      if($cid==0) {
        $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode($fileName),
                      php_xmlrpc_encode($base64Enc));
        $result = $this->methodCaller("FileService.uploadFile",$carray);
      } else {
        $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode($fileName),
                      php_xmlrpc_encode($base64Enc));
        $result = $this->methodCaller("FileService.uploadFile",$carray);
      }
      return $result;
  }

  //boolean replaceFile(String key, int fileId, String base64encoded) - returns true if successful
  public function replaceFile($fileID,$base64Enc) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$fileID),
                      php_xmlrpc_encode($base64Enc));
      $result = $this->methodCaller("FileService.replaceFile",$carray);
      return $result;
  }

  //boolean renameFile(String key, int fileId, String fileName) - returns true if successful
  public function renameFile($fileID,$fileName) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$fileID),
                      php_xmlrpc_encode($fileName));
      $result = $this->methodCaller("FileService.renameFile",$carray);
      return $result;
  }

  //String getDownloadUrl(String key, int fileId)
  public function getDownloadUrl($fileID) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$fileID));
      $result = $this->methodCaller("FileService.getDownloadUrl",$carray);
      return $result;
  }


  /////////////////////////////////////////////////////////
  ////////////////////CONTACT SERVICE////////////////////// /////////////////////////////////////////////////////////
  ###public function to add contacts to Infusion - Returns Contact ID###
  public function addCon($cMap, $optReason = "") {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode($cMap,array('auto_dates')));
      $conID = $this->methodCaller("ContactService.add",$carray);
      if (!empty($cMap['Email'])) {
        if ($optReason == "") { $this->optIn($cMap['Email']); } else { $this->optIn($cMap['Email'],$optReason); }
      }
      return $conID;
  }

  ###public function to Update Contacts in Infusion - Returns updated contacts ID###
  public function updateCon($cid, $cMap) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode($cMap,array('auto_dates')));
      return $this->methodCaller("ContactService.update",$carray);
  }
  ###function to merge existing contacts
  public function mergeCon($cid, $dcid) {
    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($cid),
        php_xmlrpc_encode($dcid));

    return $this->methodCaller("ContactService.merge",$carray);
  }
  ###Finds all contacts for an Email###
  public function findByEmail($eml, $fMap) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode($eml),
                      php_xmlrpc_encode($fMap));
      return $this->methodCaller("ContactService.findByEmail",$carray);
  }

  ###public function to load a contacts data - Returns a Key/Value array###
  public function loadCon($cid, $rFields) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode($rFields));
      return $this->methodCaller("ContactService.load",$carray);
  }

  ###public function to add a contact to a group###
  public function grpAssign($cid, $gid) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode((int)$gid));
      return $this->methodCaller("ContactService.addToGroup",$carray);
  }

  ###public function to remove a contact from a group###
  public function grpRemove($cid, $gid) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode((int)$gid));
      return $this->methodCaller("ContactService.removeFromGroup",$carray);
  }

  ###public function to add a contact to a campaign###
  public function campAssign($cid, $campId) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode((int)$campId));
      return $this->methodCaller("ContactService.addToCampaign",$carray);
  }

  ###Returns next step in a campaign###
  public function getNextCampaignStep($cid, $campId)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$cid),
      php_xmlrpc_encode((int)$campId)
    );

    return $this->methodCaller('ContactService.getNextCampaignStep', $cArray);
  }

  ###Returns step details for a contact in a campaign###
  public function getCampaigneeStepDetails($cid, $stepId)
  {
    $carray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$cid),
      php_xmlrpc_encode((int)$stepId)
    );

    return $this->methodCaller("ContactService.getCampaigneeStepDetails", $carray);
  }

  ###Reschedules a campaign step for a list of contacts###
  public function rescheduleCampaignStep($cidList, $campId)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($cidList),
      php_xmlrpc_encode((int)$campId)
    );

    return $this->methodCaller('ContactService.rescheduleCampaignStep', $cArray);
  }

  ###public function to remove a contact from a campaign###
  public function campRemove($cid, $campId)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$cid),
      php_xmlrpc_encode((int)$campId)
    );

    return $this->methodCaller('ContactService.removeFromCampaign', $cArray);
  }

  ###public function to pause a contacts campaign###
  public function campPause($cid, $campId) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$cid),
                      php_xmlrpc_encode((int)$campId));
      return $this->methodCaller("ContactService.pauseCampaign",$carray);
  }

  ###public function to run an Action Sequence###
  public function runAS($cid, $aid)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$cid),
      php_xmlrpc_encode((int)$aid)
    );

    return $this->methodCaller('ContactService.runActionSequence', $cArray);
  }

  ###public function to create a note from a note template
  public function applyActivityHistoryTemplate($contactId, $historyId, $userId)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$contactId),
      php_xmlrpc_encode((int)$historyId),
      php_xmlrpc_encode((int)$userId)
    );

    return $this->methodCaller('ContactService.applyActivityHistoryTemplate', $cArray);
  }


  /**
   * DATA SERVICE
   */

  /**
   * This method will return back the data currently configured in a user configured application setting
   *
   * @param string $module  The application module this setting is a part of
   * @param string $setting The database name of the setting you would like the values returned for
   * @return string
   * @see http://help.infusionsoft.com/api-docs/dataservice#getAppSetting
   */
  public function dsGetSetting($module, $setting)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($module),
      php_xmlrpc_encode($setting)
    );

    return $this->methodCaller('DataService.getAppSetting', $cArray);
  }

  /**
   * Adds a record to the specified table in Infusionsoft
   *
   * @param string $table  The Infusionsoft database table name
   * @param array  $values An associative array of data you would like stored into this new row in the table
   * @return integer
   * @see http://help.infusionsoft.com/api-docs/dataservice#add
   */
  public function dsAdd($table, $values)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode($values, array('auto_dates'))
    );

    return $this->methodCaller('DataService.add', $cArray);
  }

  /**
   * Adds a record to the specified table in Infusionsoft. Images will be base64 encoded
   *
   * @param string $table  The Infusionsoft database table name
   * @param array  $values An associative array of data you would like stored into this new row in the table
   * @return integer
   * @see http://help.infusionsoft.com/api-docs/dataservice#add
   */
  public function dsAddWithImage($table, $values)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode($values, array('auto_dates', 'auto_base64'))
    );

    return $this->methodCaller('DataService.add', $cArray);
  }

  /**
   * Deletes the specified record in the given table from the database
   *
   * @param string  $table The table you would like to delete the record from
   * @param integer $id    The ID number of the record you want to delete
   * @return boolean
   * @see http://help.infusionsoft.com/api-docs/dataservice#delete
   */
  public function dsDelete($table, $id)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$id)
    );

    return $this->methodCaller('DataService.delete', $cArray);
  }

  /**
   * Updates the specified record (indicated by ID) with the data provided
   *
   * @param string  $table  The Infusionsoft database table name
   * @param integer $id     The ID number of the record you would like updated on the given table
   * @param array   $values An associative array of data you would like updated
   * @return integer
   * @see http://help.infusionsoft.com/api-docs/dataservice#update
   */
  public function dsUpdate($table, $id, array $values)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$id),
      php_xmlrpc_encode($values, array('auto_dates'))
    );

    return $this->methodCaller('DataService.update', $cArray);
  }

  /**
   * Updates the specified record (indicated by ID) with the data provided. Images will be base64-encoded
   *
   * @param string  $table  The Infusionsoft database table name
   * @param integer $id     The ID number of the record you would like updated on the given table
   * @param array   $values An associative array of data you would like updated
   * @return integer
   * @see http://help.infusionsoft.com/api-docs/dataservice#update
   */
  public function dsUpdateWithImage($table, $id, $values)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$id),
      php_xmlrpc_encode($values, array('auto_dates','auto_base64'))
    );

    return $this->methodCaller('DataService.update', $cArray);
  }

  /**
   * Loads a struct with data from the given database record
   *
   * @param string  $table        Infusionsoft database table name from which you want to load a record
   * @param integer $recordId     The unique Id number for the record you would like to load
   * @param array   $wantedFields The fields you would like returned from this row in the database
   * @return array
   * @see http://help.infusionsoft.com/api-docs/dataservice#load
   */
  public function dsLoad($table, $recordId, $wantedFields)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$recordId),
      php_xmlrpc_encode($wantedFields)
    );

    return $this->methodCaller('DataService.load', $cArray);
  }

  /**
   *
   * @param string  $table      The Infusionsoft database table name
   * @param integer $limit      How many records you would like returned. The maximum possible is 1000
   * @param integer $page       The page of results you would like returned. The first page is page 0 (loop through pages to get more than 1000 records)
   * @param string $fieldName   The name of the field you would like to run the search on
   * @param string $fieldValue  The value stored in the field you would like to search by
   * @param array $returnFields The fields you would like returned from the table you are searching on
   * @return array
   * @see http://help.infusionsoft.com/api-docs/dataservice#findByField
   */
  public function dsFind($table, $limit, $page, $fieldName, $fieldValue, array $returnFields)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$limit),
      php_xmlrpc_encode((int)$page),
      php_xmlrpc_encode($fieldName),
      php_xmlrpc_encode($fieldValue),
      php_xmlrpc_encode($returnFields)
    );

    return $this->methodCaller('DataService.findByField', $cArray);
  }

  ###public function for DataService.query method###
  public function dsQuery($table, $limit, $page, $query, array $returnFields)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$limit),
      php_xmlrpc_encode((int)$page),
      php_xmlrpc_encode($query, array('auto_dates')),
      php_xmlrpc_encode($returnFields)
    );

    return $this->methodCaller('DataService.query', $cArray);
  }

  public function dsQueryOrderBy($table, $limit, $page, $query, $rFields, $orderByField, $ascending = true)
  {
    $carray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($table),
      php_xmlrpc_encode((int)$limit),
      php_xmlrpc_encode((int)$page),
      php_xmlrpc_encode($query, array('auto_dates')),
      php_xmlrpc_encode($rFields),
      php_xmlrpc_encode($orderByField),
      php_xmlrpc_encode((bool)$ascending)
    );

    return $this->methodCaller("DataService.query",$carray);
  }

  ###Adds a custom field to Infusionsoft###
  public function addCustomField($context,$displayName,$dataType,$groupID) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode($context),
                      php_xmlrpc_encode($displayName),
                      php_xmlrpc_encode($dataType),
                      php_xmlrpc_encode((int)$groupID));

      return $this->methodCaller("DataService.addCustomField",$carray);
  }

  ###Authenticates a user account in Infusionsoft###
  public function authenticateUser($userName,$password) {
      $password = strtolower(md5($password));
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode($userName),
                      php_xmlrpc_encode($password));

      return $this->methodCaller("DataService.authenticateUser",$carray);
  }

  ###Updates a custom field###
  public function updateCustomField($fieldId, $fieldValues) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$fieldId),
                      php_xmlrpc_encode($fieldValues));
      return $this->methodCaller("DataService.updateCustomField",$carray);
  }

  /////////////////////////////////////////////////////////
  ////////////////////INVOICE SERVICE////////////////////// /////////////////////////////////////////////////////////

  public function deleteInvoice($Id) {
      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$Id));
      return $this->methodCaller("InvoiceService.deleteInvoice",$carray);
  }

  public function deleteSubscription($Id) {
      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$Id));
      return $this->methodCaller("InvoiceService.deleteSubscription",$carray);
  }

  /*
  public void setInvoiceSyncStatus(int id, boolean syncStatus); public void setPaymentSyncStatus(int id, boolean syncStatus); public String getPluginStatus(String fullyQualifiedClassName); public List getAllShippingOptions(); public Map getAllPaymentOptions(); public list getPayments(); */

  ###Get a list of payments on an invoice### ###Find the id of the invoice attached to a one-time order###
  public function getPayments($Id) {
      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$Id));
      return $this->methodCaller("InvoiceService.getPayments",$carray);
  }
  //////////////////////////////////////////////////////////////////////////

  ###Find the id of the invoice attached to a one-time order###
  public function setInvoiceSyncStatus($Id,$syncStatus) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$Id),
          php_xmlrpc_encode($syncStatus));
      return
  $this->methodCaller("InvoiceService.setInvoiceSyncStatus",$carray);
  }
  //////////////////////////////////////////////////////////////////////////
  public function setPaymentSyncStatus($Id,$Status) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$Id),
          php_xmlrpc_encode($Status));
      return
  $this->methodCaller("InvoiceService.setPaymentSyncStatus",$carray);
  }
  ///////////////////////////////////////////////////////////////////////////
  public function getPluginStatus($className) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($className));
      return $this->methodCaller("InvoiceService.getPluginStatus",$carray);
  }
  ///////////////////////////////////////////////////////////////////////////
  public function getAllShippingOptions() {
      $carray = array(
          php_xmlrpc_encode($this->key));
      return
  $this->methodCaller("InvoiceService.getAllShippingOptions",$carray);
  }
  ///////////////////////////////////////////////////////////////////////////
  public function getAllPaymentOptions() {
      $carray = array(
          php_xmlrpc_encode($this->key));
      return
  $this->methodCaller("InvoiceService.getAllPaymentOptions",$carray);
  }
  //////////////////////////////////////////////////////////////////////


  public function
  manualPmt($invId,$amt,$payDate,$payType,$payDesc,$bypassComm) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$invId),
          php_xmlrpc_encode($amt),
          php_xmlrpc_encode($payDate,array('auto_dates')),
          php_xmlrpc_encode($payType),
          php_xmlrpc_encode($payDesc),
          php_xmlrpc_encode($bypassComm));
      return $this->methodCaller("InvoiceService.addManualPayment",$carray);
  }

  ###public function to Override Order Commisions - InvoiceService.addOrderCommissionOverride###
  public function commOverride($invId,$affId,$prodId,$percentage,$amt,$payType,$desc,$date) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$invId),
            php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode((int)$prodId),
          php_xmlrpc_encode($percentage),
          php_xmlrpc_encode($amt),
          php_xmlrpc_encode($payType),
          php_xmlrpc_encode($desc),
          php_xmlrpc_encode($date,array('auto_dates')));

      return
  $this->methodCaller("InvoiceService.addOrderCommissionOverride",$carray);
  }

  ###public function to add an item to an order - InvoiceService.addOrderItem###
  public function addOrderItem($ordId,$prodId,$type,$price,$qty,$desc,$notes)
  {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$ordId),
          php_xmlrpc_encode((int)$prodId),
          php_xmlrpc_encode($type),
          php_xmlrpc_encode($price),
          php_xmlrpc_encode($qty),
          php_xmlrpc_encode($desc),
          php_xmlrpc_encode($notes));

      return $this->methodCaller("InvoiceService.addOrderItem",$carray);
  }

  ###public function to add payment plans to orders - InvoiceService.addPaymentPlan###
  public function payPlan($ordId,$aCharge,$ccId,$merchId,$retry,$retryAmt,$initialPmt,$initialPmtDate,$planStartDate,$numPmts,$pmtDays) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$ordId),
          php_xmlrpc_encode($aCharge),
          php_xmlrpc_encode((int)$ccId),
          php_xmlrpc_encode((int)$merchId),
          php_xmlrpc_encode((int)$retry),
          php_xmlrpc_encode((int)$retryAmt),
          php_xmlrpc_encode($initialPmt),
          php_xmlrpc_encode($initialPmtDate,array('auto_dates')),
          php_xmlrpc_encode($planStartDate,array('auto_dates')),
          php_xmlrpc_encode((int)$numPmts),
          php_xmlrpc_encode((int)$pmtDays));

      return $this->methodCaller("InvoiceService.addPaymentPlan",$carray);
  }

  ###public function to Override Recurring Order Commisions - InvoiceService.addOrderCommissionOverride###
  public function recurringCommOverride($recId,$affId,$amt,$payType,$desc) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$recId),
          php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode($amt),
          php_xmlrpc_encode($payType),
          php_xmlrpc_encode($desc));

      return
  $this->methodCaller("InvoiceService.addRecurringCommissionOverride",$carray)
  ;
  }

  ###public function to add a recurring order - InvoiceService.addRecurringOrder###
  public function addRecurring($cid,$allowDup,$progId,$merchId,$ccId,$affId,$daysToCharge) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$cid),
          php_xmlrpc_encode($allowDup),
          php_xmlrpc_encode((int)$progId),
          php_xmlrpc_encode((int)$merchId),
          php_xmlrpc_encode((int)$ccId),
          php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode($daysToCharge));
      return $this->methodCaller("InvoiceService.addRecurringOrder",$carray);
  }

  ###public function to add a recurring order - InvoiceService.addRecurringOrder - Allows Quantity, Price and Tax###
  public function addRecurringAdv($cid,$allowDup,$progId,$qty,$price,$allowTax,$merchId,$ccId,$affId,$daysToCharge) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$cid),
          php_xmlrpc_encode($allowDup),
          php_xmlrpc_encode((int)$progId),
          php_xmlrpc_encode($qty),
          php_xmlrpc_encode($price),
          php_xmlrpc_encode($allowTax),
          php_xmlrpc_encode($merchId),
          php_xmlrpc_encode((int)$ccId),
          php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode($daysToCharge));
      return $this->methodCaller("InvoiceService.addRecurringOrder",$carray);
  }

  ###public function to get the Amount owed on an invoice - InvoiceService.calculateAmountOwed###
  public function amtOwed($invId) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$invId));

      return
  $this->methodCaller("InvoiceService.calculateAmountOwed",$carray);
  }

  ###Find the id of the invoice attached to a one-time order###
  public function getInvoiceId($orderId) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$orderId));

      return $this->methodCaller("InvoiceService.getInvoiceId",$carray);
  }

  ###Find the id of an order using an invoice ID.###
  public function getOrderId($invoiceId) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$invoiceId));

      return $this->methodCaller("InvoiceService.getOrderId",$carray);
  }

  ###public function to charge invoices - InvoiceService.chargeInvoice###
  public function chargeInvoice($invId,$notes,$ccId,$merchId,$bypassComm) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$invId),
          php_xmlrpc_encode($notes),
          php_xmlrpc_encode((int)$ccId),
          php_xmlrpc_encode((int)$merchId),
          php_xmlrpc_encode($bypassComm));

      return $this->methodCaller("InvoiceService.chargeInvoice",$carray);
  }

  ###public function to create blank orders - InvoiceService.createBlankOrder###
  public function blankOrder($conId,$desc,$oDate,$leadAff,$saleAff) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$conId),
          php_xmlrpc_encode($desc),
          php_xmlrpc_encode($oDate,array('auto_dates')),
          php_xmlrpc_encode((int)$leadAff),
          php_xmlrpc_encode((int)$saleAff));

      return $this->methodCaller("InvoiceService.createBlankOrder",$carray);
  }

  ###public function to create an invioce for recurring orders - InvoiceService.createInvoiceForRecurring###
  public function recurringInvoice($rid) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$rid));

      return
  $this->methodCaller("InvoiceService.createInvoiceForRecurring",$carray);
  }

  ###public function to locate creditcards based on the last 4 digits - InvoiceService.locateExistingCard###
  public function locateCard($cid,$last4) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$cid),
          php_xmlrpc_encode($last4));

      return $this->methodCaller("InvoiceService.locateExistingCard",$carray);
  }

  ###public function to Validate Credit Cards - InvoiceService.validateCreditCard###
  ###This public function will take a CC ID or a CC Map###
  public function validateCard($creditCard) {

      $creditCard = is_array($creditCard) ? $creditCard : (int)$creditCard;

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($creditCard));

      return $this->methodCaller("InvoiceService.validateCreditCard",$carray);
  }

  ###Updates the Next Bill Date on a Subscription###
  public function updateSubscriptionNextBillDate($subscriptionId,$nextBillDate) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$subscriptionId),
                      php_xmlrpc_encode($nextBillDate,array('auto_dates')));

      return
  $this->methodCaller("InvoiceService.updateJobRecurringNextBillDate",$carray)
  ;
  }

  #############################
  ##### API EMAIL SERVICE #####
  #############################

  ###This function will attach an email to a contacts email history###
  public function attachEmail($cId, $fromName, $fromAddress, $toAddress, $ccAddresses,
                              $bccAddresses, $contentType, $subject, $htmlBody, $txtBody,
                              $header, $strRecvdDate, $strSentDate,$emailSentType=1) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$cId),
          php_xmlrpc_encode($fromName),
          php_xmlrpc_encode($fromAddress),
          php_xmlrpc_encode($toAddress),
          php_xmlrpc_encode($ccAddresses),
          php_xmlrpc_encode($bccAddresses),
          php_xmlrpc_encode($contentType),
          php_xmlrpc_encode($subject),
          php_xmlrpc_encode($htmlBody),
          php_xmlrpc_encode($txtBody),
          php_xmlrpc_encode($header),
          php_xmlrpc_encode($strRecvdDate),
          php_xmlrpc_encode($strSentDate),
          php_xmlrpc_encode($emailSentType));

      return $this->methodCaller("APIEmailService.attachEmail",$carray);
  }

  ###Function to obtain Available Merge Fields###
  public function getAvailableMergeFields($mergeContext) {
    $carray = array(
        php_xmlrpc_encode($this->key),
        php_xmlrpc_encode($mergeContext));
    return $this->methodCaller("APIEmailService.getAvailableMergeFields", $carray);
  }

  ###This function will send an email to an array contacts###
  public function sendEmail($conList, $fromAddress, $toAddress, $ccAddresses, $bccAddresses, $contentType, $subject, $htmlBody, $txtBody) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($conList),
          php_xmlrpc_encode($fromAddress),
          php_xmlrpc_encode($toAddress),
          php_xmlrpc_encode($ccAddresses),
          php_xmlrpc_encode($bccAddresses),
          php_xmlrpc_encode($contentType),
          php_xmlrpc_encode($subject),
          php_xmlrpc_encode($htmlBody),
          php_xmlrpc_encode($txtBody));

      return $this->methodCaller("APIEmailService.sendEmail",$carray);
  }


  ###This function will send an email to an array contacts###
  public function sendTemplate($conList, $template) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($conList),
          php_xmlrpc_encode($template));

      return $this->methodCaller("APIEmailService.sendEmail",$carray);
  }

  //THIS IS DEPRECATED - USE addEmailTemplate instead!
  /**
   *
   * @param type $title
   * @param type $userID
   * @param type $fromAddress
   * @param type $toAddress
   * @param type $ccAddresses
   * @param type $bccAddresses
   * @param type $contentType
   * @param type $subject
   * @param type $htmlBody
   * @param type $txtBody
   * @return type
   * @deprecated
   */
  public function createEmailTemplate(
      $title,
      $userID,
      $fromAddress,
      $toAddress,
      $ccAddresses,
      $bccAddresses,
      $contentType,
      $subject,
      $htmlBody,
      $txtBody
  )
  {
    trigger_error('This function is deprecated. Use "addEmailTemplate" instead', 'E_DEPRECATED');

    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($title),
      php_xmlrpc_encode((int)$userID),
      php_xmlrpc_encode($fromAddress),
      php_xmlrpc_encode($toAddress),
      php_xmlrpc_encode($ccAddresses),
      php_xmlrpc_encode($bccAddresses),
      php_xmlrpc_encode($contentType),
      php_xmlrpc_encode($subject),
      php_xmlrpc_encode($htmlBody),
      php_xmlrpc_encode($txtBody)
    );

    return $this->methodCaller('APIEmailService.createEmailTemplate', $cArray);
  }

  /**
   *
   * @param type $title
   * @param type $category
   * @param type $fromAddress
   * @param type $toAddress
   * @param type $ccAddresses
   * @param type $bccAddresses
   * @param type $subject
   * @param type $txtBody
   * @param type $htmlBody
   * @param type $contentType
   * @param type $mergeContext
   * @return type
   */
  public function addEmailTemplate(
      $title,
      $category,
      $fromAddress,
      $toAddress,
      $ccAddresses,
      $bccAddresses,
      $subject,
      $txtBody,
      $htmlBody,
      $contentType,
      $mergeContext
  )
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($title),
      php_xmlrpc_encode($category),
      php_xmlrpc_encode($fromAddress),
      php_xmlrpc_encode($toAddress),
      php_xmlrpc_encode($ccAddresses),
      php_xmlrpc_encode($bccAddresses),
      php_xmlrpc_encode($subject),
      php_xmlrpc_encode($txtBody),
      php_xmlrpc_encode($htmlBody),
      php_xmlrpc_encode($contentType),
      php_xmlrpc_encode($mergeContext)
    );

    return $this->methodCaller('APIEmailService.addEmailTemplate', $cArray);
  }

  ###Function to get an email template###
  public function getEmailTemplate($templateId) {
    $carray = array(php_xmlrpc_encode($this->key),
  php_xmlrpc_encode((int)$templateId));
    return $this->methodCaller("APIEmailService.getEmailTemplate",$carray);
  }

  /*
  boolean updateEmailTemplate(
      string  key,
      int     templateId,
      string  pieceTitle,
      string  categories,
      string  fromAddress,
      string  toAddress,
      string  ccAddress,
      string  bccAddress,
      string  subject,
      string  textBody,
      string  htmlBody,
      string  contentType,
      string  mergeContext
  )
  */

  ###Function to update an email template###
  public function updateEmailTemplate($templateID, $title, $categories, $fromAddress, $toAddress, $ccAddress, $bccAddress, $subject, $textBody, $htmlBody, $contentType, $mergeContext) {
    $carray = array(php_xmlrpc_encode($this->key),
                    php_xmlrpc_encode((int)$templateID),
                    php_xmlrpc_encode($title),
                    php_xmlrpc_encode($categories),
                    php_xmlrpc_encode($fromAddress),
                    php_xmlrpc_encode($toAddress),
                    php_xmlrpc_encode($ccAddress),
                    php_xmlrpc_encode($bccAddress),
                    php_xmlrpc_encode($subject),
                    php_xmlrpc_encode($textBody),
                    php_xmlrpc_encode($htmlBody),
                    php_xmlrpc_encode($contentType),
                    php_xmlrpc_encode($mergeContext));
    return $this->methodCaller("APIEmailService.updateEmailTemplate",$carray);
  }

  ###Function to obtain an opt status###
  public function optStatus($email) {
      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($email));
      return $this->methodCaller("APIEmailService.getOptStatus", $carray); }


  ###Functions to opt people in/out.###
  ###Note that Opt-In will only work on "non-marketable contacts not opted out people.###
  public function optIn($email, $reason='API Opt In') {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($email),
          php_xmlrpc_encode($reason));

      return $this->methodCaller("APIEmailService.optIn",$carray);
  }

  public function optOut($email, $reason='API Opt Out') {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($email),
          php_xmlrpc_encode($reason));

      return $this->methodCaller("APIEmailService.optOut",$carray);
  }

  ////////////////////////////////////////////////////////
  ////////////////AFFILIATE SYSTEM FUNCTIONS////////////// ////////////////////////////////////////////////////////

  ###This function will return all claw backs in a date range###
  public function affClawbacks($affId, $startDate, $endDate) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode($startDate,array('auto_dates')),
          php_xmlrpc_encode($endDate,array('auto_dates')));

      return $this->methodCaller("APIAffiliateService.affClawbacks",$carray);
  }

  ###This function will return all commissions in a date range###
  public function affCommissions($affId, $startDate, $endDate) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode($startDate,array('auto_dates')),
          php_xmlrpc_encode($endDate,array('auto_dates')));

      return
  $this->methodCaller("APIAffiliateService.affCommissions",$carray);
  }

  ###This function will return all payouts in a date range###
  public function affPayouts($affId, $startDate, $endDate) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$affId),
          php_xmlrpc_encode($startDate,array('auto_dates')),
          php_xmlrpc_encode($endDate,array('auto_dates')));

      return $this->methodCaller("APIAffiliateService.affPayouts",$carray);
  }

  ###Returns a list with each row representing a single affiliates totals represented by a map with key (one of the names above, and value being the total for that variable)###
  public function affRunningTotals($affList) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($affList));

      return
  $this->methodCaller("APIAffiliateService.affRunningTotals",$carray);
  }

  ###This function will return how much the specified affiliates are owed###
  public function affSummary($affList, $startDate, $endDate) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($affList),
          php_xmlrpc_encode($startDate,array('auto_dates')),
          php_xmlrpc_encode($endDate,array('auto_dates')));

      return $this->methodCaller("APIAffiliateService.affSummary",$carray);
  }

  ////////////////////////////////////////////////////////
  //////////////// TICKET SYSTEM FUNCTIONS /////////////// ////////////////////////////////////////////////////////

  ###This function Adds move notes to existing tickets###
  public function addMoveNotes($ticketList, $moveNotes, $moveToStageId,
  $notifyIds) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($ticketList),
          php_xmlrpc_encode($moveNotes),
          php_xmlrpc_encode($moveToStageId),
          php_xmlrpc_encode($notifyIds));

      return $this->methodCaller("ServiceCallService.addMoveNotes",$carray);
  }

  ###This function Adds move notes to existing tickets###
  public function moveTicketStage($ticketID, $ticketStage, $moveNotes,
  $notifyIds) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$ticketID),
          php_xmlrpc_encode($ticketStage),
          php_xmlrpc_encode($moveNotes),
          php_xmlrpc_encode($notifyIds));

      return
  $this->methodCaller("ServiceCallService.moveTicketStage",$carray);
  }

  /////////////////////////////////////////////////////////
  ////////////////ADDITIONAL public functionS////////////// /////////////////////////////////////////////////////////

  ###public function to return properly formatted dates.
  public function infuDate($dateStr) {
      $dArray=date_parse($dateStr);
      if ($dArray['error_count']<1) {
          $tStamp =
  mktime($dArray['hour'],$dArray['minute'],$dArray['second'],$dArray['month'],
  $dArray['day'],$dArray['year']);
          return date('Ymd\TH:i:s',$tStamp);
      } else {
          foreach ($dArray['errors'] as $err) {
              echo "ERROR: " . $err . "<br />";
          }
          die("The above errors prevented the application from executing properly.");
      }
  }

  /////////////////////////////////////////////////////////
  ////////////////SearchService public functions////////////// /////////////////////////////////////////////////////////

  ###public function to return a saved search with all fields
  public function savedSearchAllFields($savedSearchId, $userId, $page) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$savedSearchId),
          php_xmlrpc_encode((int)$userId),
          php_xmlrpc_encode((int)$page));

      return
  $this->methodCaller("SearchService.getSavedSearchResultsAllFields",$carray);
  }

  ###public function to return a saved search with selected fields
  public function savedSearch($savedSearchId, $userId, $page, $fields) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$savedSearchId),
          php_xmlrpc_encode((int)$userId),
          php_xmlrpc_encode((int)$page),
          php_xmlrpc_encode($fields));

      return
  $this->methodCaller("SearchService.getSavedSearchResults",$carray);
  }

  ###public function to return the fields available in a saved report
  public function getAvailableFields($savedSearchId, $userId) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$savedSearchId),
          php_xmlrpc_encode((int)$userId));

      return $this->methodCaller("SearchService.getAllReportColumns",$carray);
  }

  ###public function to return the default quick search type for a user
  public function getDefaultQuickSearch($userId) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$userId));

      return
  $this->methodCaller("SearchService.getDefaultQuickSearch",$carray);
  }

  ###public function to return the available quick search types
  public function getQuickSearches($userId) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode((int)$userId));

      return
  $this->methodCaller("SearchService.getAvailableQuickSearches",$carray);
  }

  ###public function to return the results of a quick search
  public function quickSearch($quickSearchType, $userId, $filterData, $page,
  $limit) {

      $carray = array(
          php_xmlrpc_encode($this->key),
          php_xmlrpc_encode($quickSearchType),
          php_xmlrpc_encode((int)$userId),
          php_xmlrpc_encode($filterData),
          php_xmlrpc_encode((int)$page),
          php_xmlrpc_encode((int)$limit));

      return $this->methodCaller("SearchService.quickSearch",$carray);
  }

  ###public function to add a contact while checking for a duplicate recorde###
  public function addWithDupCheck($cMap, $checkType) {

      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode($cMap,array('auto_dates')),
                      php_xmlrpc_encode($checkType));
      return $this->methodCaller("ContactService.addWithDupCheck",$carray);
  }

  ###This function will recalculate tax for a given invoice Id
  public function recalculateTax ($invoiceId) {
    $carray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$invoiceId));

    return $this->methodCaller("InvoiceService.recalculateTax",$carray);
  }

  ###public function to return web form titles and Id numbers from the application
  public function getWebFormMap() {
    $carray = array(php_xmlrpc_encode($this->key));
    return $this->methodCaller("WebFormService.getMap",$carray);
  }

  ###public function to return the HTML for the given web form
  public function getWebFormHtml($webFormId=0) {
    $carray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$webFormId));
    return $this->methodCaller("WebFormService.getHTML",$carray);
  }

  /////////////////////////////////////////////////////////
  ////////////////ProductService functionS////////////// /////////////////////////////////////////////////////////

  ###public function to retrieve the current inventory level for a specific product
  public function getInventory($productId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$productId));
      return $this->methodCaller("ProductService.getInventory",$carray);
  }

  ###public function to increment current inventory level by 1
  public function incrementInventory($productId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$productId));
      return $this->methodCaller("ProductService.incrementInventory",$carray);
  }

  ###public function to decrement current inventory level by 1
  public function decrementInventory($productId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$productId));
      return $this->methodCaller("ProductService.decrementInventory",$carray);
  }

  ###public function to increment current inventory levels
  public function increaseInventory($productId, $quantity) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$productId),
                      php_xmlrpc_encode((int)$quantity));
      return $this->methodCaller("ProductService.increaseInventory",$carray);
  }

  ###public function to decrement current inventory levels
  public function decreaseInventory($productId, $quantity) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$productId),
                      php_xmlrpc_encode((int)$quantity));
      return $this->methodCaller("ProductService.decreaseInventory",$carray);
  }

  ###public function to deactivate credit cards
  public function deactivateCreditCard($creditCardId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$creditCardId));
      return $this->methodCaller("ProductService.deactivateCreditCard",$carray);
  }

  /////////////////////////////////////////////////////////
  ////////////////ShippingService functionS////////////// /////////////////////////////////////////////////////////

  ###public function to retrieve basic info about all configured shipping options
  public function getAllConfiguredShippingOptions() {
      $carray = array(
                      php_xmlrpc_encode($this->key));
      return $this->methodCaller("ShippingService.getAllShippingOptions",$carray);
  }

  ###public function to retrieve details on a flate rate type shipping options
  public function getFlatRateShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getFlatRateShippingOption",$carray);
  }

  ###public function to retrieve details on a order total type shipping options
  public function getOrderTotalShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getOrderTotalShippingOption",$carray);
  }

  ###public function to retrieve the pricing range details for the given Order Total shipping option
  public function getOrderTotalShippingRanges($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getOrderTotalShippingRanges",$carray);
  }

  ###public function to retrieve details on a product based type shipping option
  public function getProductBasedShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getProductBasedShippingOption",$carray);
  }

  ###public function to retrieve the pricing for your per product shipping options
  public function getProductShippingPricesForProductShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getProductShippingPricesForProductShippingOption",$carray);
  }

  ###public function to retrieve details on a order quantity type shipping option
  public function getOrderQuantityShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getOrderQuantityShippingOption",$carray);
  }

  ###public function to retrieve details on a weight based type shipping option
  public function getWeightBasedShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getWeightBasedShippingOption",$carray);
  }

  ###public function to retrieve the weight ranges for a weight based type shipping option
  public function getWeightBasedShippingRanges($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getWeightBasedShippingRanges",$carray);
  }

  ###public function to retrieve the details around a UPS type shipping option
  public function getUpsShippingOption($optionId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$optionId));
      return $this->methodCaller("ShippingService.getUpsShippingOption",$carray);
  }

  /////////////////////////////////////////////////////////
  ////////////////DiscountService functionS////////////// /////////////////////////////////////////////////////////

  ###public function to create a subscription free trial for the shopping cart
  public function addFreeTrial($name, $description, $freeTrialDays, $hidePrice, $subscriptionPlanId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((string)$name),
                      php_xmlrpc_encode((string)$description),
                      php_xmlrpc_encode((int)$freeTrialDays),
                      php_xmlrpc_encode((int)$hidePrice),
                      php_xmlrpc_encode((int)$subscriptionPlanId));
      return $this->methodCaller("DiscountService.addFreeTrial",$carray);
  }

  ###public function to retrieve the details on the given free trial
  public function getFreeTrial($trialId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$trialId));
      return $this->methodCaller("DiscountService.getFreeTrial",$carray);
  }

  ###public function to create an order total discount for the shopping cart
  //@param: $percentOrAmt - 0 = Amount, 1 = Percent
  //@param: $payType - must be "Gross" or "Net"
  public function addOrderTotalDiscount($name, $description, $applyDiscountToCommission, $percentOrAmt, $amt, $payType) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((string)$name),
                      php_xmlrpc_encode((string)$description),
                      php_xmlrpc_encode((int)$applyDiscountToCommission),
                      php_xmlrpc_encode((int)$percentOrAmt),
                      php_xmlrpc_encode($amt),
                      php_xmlrpc_encode($payType));
      return $this->methodCaller("DiscountService.addOrderTotalDiscount",$carray);
  }

  ###public function to retrieve the details on the given order total discount
  public function getOrderTotalDiscount($id) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$id));
      return $this->methodCaller("DiscountService.getOrderTotalDiscount",$carray);
  }

  ###public function to create a product category discount for the shopping cart
  public function addCategoryDiscount($name, $description, $applyDiscountToCommission, $amt) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((string)$name),
                      php_xmlrpc_encode((string)$description),
                      php_xmlrpc_encode((int)$applyDiscountToCommission),
                      php_xmlrpc_encode($amt));
      return $this->methodCaller("DiscountService.addCategoryDiscount",$carray);
  }

  ###public function to retrieve the details on the Category discount
  public function getCategoryDiscount($id) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$id));
      return $this->methodCaller("DiscountService.getCategoryDiscount",$carray);
  }

  ###public function to assign a product category to a particular category discount
  public function addCategoryAssignmentToCategoryDiscount($categoryDiscountId, $productCategoryId) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$categoryDiscountId),
                      php_xmlrpc_encode((int)$productCategoryId));
      return $this->methodCaller("DiscountService.addCategoryAssignmentToCategoryDiscount",$carray);
  }

  ###public function to retrieve the product categories that are currently set for the given category discount
  public function getCategoryAssignmentsForCategoryDiscount($id) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$id));
      return $this->methodCaller("DiscountService.getCategoryAssignmentsForCategoryDiscount",$carray);
  }

  ###public function to create an product total discount for the shopping cart
  //@param: $percentOrAmt - 0 = Amount, 1 = Percent
  public function addProductTotalDiscount($name, $description, $applyDiscountToCommission, $productId, $percentOrAmt, $amt) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((string)$name),
                      php_xmlrpc_encode((string)$description),
                      php_xmlrpc_encode((int)$applyDiscountToCommission),
                      php_xmlrpc_encode((int)$productId),
                      php_xmlrpc_encode((int)$percentOrAmt),
                      php_xmlrpc_encode($amt));
      return $this->methodCaller("DiscountService.addProductTotalDiscount",$carray);
  }

  ###public function to retrieve the details on the given product total discount
  public function getProductTotalDiscount($id) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((int)$id));
      return $this->methodCaller("DiscountService.getProductTotalDiscount",$carray);
  }

  ###public function to create an shipping total discount for the shopping cart
  //@param: $percentOrAmt - 0 = Amount, 1 = Percent
  public function addShippingTotalDiscount($name, $description, $applyDiscountToCommission, $percentOrAmt, $amt) {
      $carray = array(
                      php_xmlrpc_encode($this->key),
                      php_xmlrpc_encode((string)$name),
                      php_xmlrpc_encode((string)$description),
                      php_xmlrpc_encode((int)$applyDiscountToCommission),
                      php_xmlrpc_encode((int)$percentOrAmt),
                      php_xmlrpc_encode($amt));
      return $this->methodCaller("DiscountService.addShippingTotalDiscount",$carray);
  }

  /**
   * Retrieves the details on the given shipping total discount
   *
   * @param string|integer $id
   * @return mixed
   */
  public function getShippingTotalDiscount($id)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$id)
    );

    return $this->methodCaller('DiscountService.getShippingTotalDiscount', $cArray);
  }


  /**
   * ORDER SERVICE FUNCTIONS
   */

  /**
   * Builds, creates and charges an order.  The ids of the order and invoice that were created are returned along
   * with the status of a credit card charge if one was made.  The credit card and payment plan specifications are optional
   * and can be set to zero to prevent the system from attempting to charge a card or process the payment under a payment
   * plan.  Special processing can be turned on or off, turning it off will ignore the promo codes specified.
   * @param contactId The id of the contact to place on the order.
   * @param creditCardId The id of the credit card to charge, leave it at zero to indicate no credit card.
   * @param payPlanId The id of the payment plan to use in building the order.  If no pay plan is specified then the
   *        default payment plan is used.
   * @param productIds The list of products to purchase on the order, this cannot be empty if no subscription plans are
   *        specified.
   * @param subscriptionPlanIds The list of subscriptions to purchase on the order, this cannot be empty if no products are
   *        specified. Note that including subscriptionPlanIds will also generate the RecurringOrder
   * @param processSpecials Whether or not the order should consider discounts that would normally be applied if this order
   *        was being placed through the shopping cart.
   * @param promoCodes Any promo codes to add to the cart, only used if processing of specials is turned on.
   * @param leadAff is the Lead Affiliate.
   * @param saleAff is the Affiliate of the sale.
   * @return The result of the order placement.
   */
  public function placeOrder(
      $contactId,
      $creditCardId,
      $payPlanId,
      $productIds,
      $subscriptionIds,
      $processSpecials,
      $promoCodes,
      $leadAff = 0,
      $saleAff = 0
  )
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$contactId),
      php_xmlrpc_encode((int)$creditCardId),
      php_xmlrpc_encode((int)$payPlanId),
      php_xmlrpc_encode($productIds),
      php_xmlrpc_encode($subscriptionIds),
      php_xmlrpc_encode($processSpecials),
      php_xmlrpc_encode($promoCodes),
      php_xmlrpc_encode((int)$leadAff),
      php_xmlrpc_encode((int)$saleAff)
    );

    return $this->methodCaller('OrderService.placeOrder', $cArray);
  }


  /**
   * CREDIT CARD SUBMISSION SERVICE FUNCTIONS
   */

  /**
   * This method gets a token, which is needed to POST a credit card to the application
   *
   * @param integer $contactId  The contact you are adding the credit card to
   * @param string  $successUrl The URL the browser is sent to upon successfully adding a credit card record
   * @param string  $failureUrl The URL the browser is sent to upon failure of adding credit card
   * @return string The token to use in your HTTP POST, which sends the CC to the app
  */
  public function requestCcSubmissionToken($contactId, $successUrl, $failureUrl)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((int)$contactId),
      php_xmlrpc_encode((string)$successUrl),
      php_xmlrpc_encode((string)$failureUrl)
    );

    return $this->methodCaller('CreditCardSubmissionService.requestSubmissionToken', $cArray);
  }

  /**
   * Retrieves credit card details that have been posted to the app
   *
   * @param string $token The token used to send the CC to the app
   * @return array An array of CC details. The CC number is not included
   */
  public function requestCreditCardId($token)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($token)
    );

    return $this->methodCaller('CreditCardSubmissionService.requestCreditCardId', $cArray);
  }


  /**
   * Funnel Service - Campaign Builder
   */

  /**
   * This method achieves a goal inside of the Campaign Builder to start a campaign
   *
   * @param string         $integration The integration name of the goal. This defaults to the name of the app.
   * @param string         $callName    The call name of the campaign
   * @param string|integer $contactId   The ID of the contact you want to add to a sequence
   * @return string The token to use in your HTTP POST, which sends the CC to the app
   * @see http://help.infusionsoft.com/api-docs/funnelservice#achieveGoal
   */
  public function achieveGoal($integration, $callName, $contactId)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode((string)$integration),
      php_xmlrpc_encode((string)$callName),
      php_xmlrpc_encode((int)$contactId)
    );

    return $this->methodCaller('FunnelService.achieveGoal', $cArray);
  }


  /**
   * Affiliate Program Service
   */

  /**
   * Gets a list of all of the affiliates with their contact data for the specified program.
   * This includes all of the custom fields defined for the contact and affiliate records that are retrieved
   *
   * @param string|integer $programId The Referral Partner Commission Program ID
   * @return mixed
   * @see http://help.infusionsoft.com/api-docs/affiliateprogramservice#getAffiliateByProgram
   */
  public function getAffiliatesByProgram($programId){
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($programId)
    );

    return $this->methodCaller('AffiliateProgramService.getAffiliatesByProgram', $cArray);
  }

  /**
   * Gets a list of all of the Affiliate Programs for the Affiliate specified
   *
   * @param integer $affiliateId The affiliate you want to get the programs for
   * @return mixed
   * @see http://help.infusionsoft.com/api-docs/affiliateprogramservice#getProgramsForAffiliate
   */
  public function getProgramsForAffiliate($affiliateId){
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($affiliateId)
    );

    return $this->methodCaller('AffiliateProgramService.getProgramsForAffiliate', $cArray);
  }

  /**
   * Gets a list of all of the Affiliate Programs that are in the application
   *
   * @return mixed
   * @see http://help.infusionsoft.com/api-docs/affiliateprogramservice#getAffiliatePrograms
   */
  public function getAffiliatePrograms()
  {
    $cArray = array(
      php_xmlrpc_encode($this->key)
    );

    return $this->methodCaller('AffiliateProgramService.getAffiliatePrograms', $cArray);
  }

  /**
   * Gets a list of all of the resources that are associated to the Affiliate Program specified
   *
   * @param integer $programId The commission program you want the resources for
   * @return mixed
   * @see http://help.infusionsoft.com/api-docs/affiliateprogramservice#getResourcesForAffiliateProgram
   */
  public function getResourcesForAffiliateProgram($programId)
  {
    $cArray = array(
      php_xmlrpc_encode($this->key),
      php_xmlrpc_encode($programId)
    );

    return $this->methodCaller('AffiliateProgramService.getResourcesForAffiliateProgram', $cArray);
  }

  /**
   * Return possible keys, to keep code simple and more reusable
   *
   * @return array
   */
  static public function getKeyTypes()
  {
    return array(
      self::VAL_ON,
      self::VAL_OFF,
      self::VAL_KILL,
      self::VAL_THROW,
    );
  }
}

class iSDKException extends Exception
{
}
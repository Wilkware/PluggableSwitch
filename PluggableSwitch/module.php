<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// PluggableSwitch
class PluggableSwitch extends IPSModuleStrict
{
    // Helper Traits
    use DebugHelper;
    use EventHelper;
    use ProfileHelper;
    use VariableHelper;

    /**
     * @var int Schedule OFF(1)
     */
    public const SCHEDULE_OFF = 1;

    /**
     * @var int Schedule ON(2)
     */
    public const SCHEDULE_ON = 2;

    /**
     * @var string Schedule Name
     */
    public const SCHEDULE_NAME = 'Zeitplan';

    /**
     * @var string Schedule Ident
     */
    public const SCHEDULE_IDENT = 'CircuitDiagram';

    /**
     * @var array<int,mixed> Schedule Switch
     */
    public const SCHEDULE_SWITCH = [
        self::SCHEDULE_OFF => ['OFF', 0xFF0000, "IPS_RequestAction(\$_IPS['TARGET'], 'CircuitDiagram', \$_IPS['ACTION']);"],
        self::SCHEDULE_ON  => ['ON', 0x00FF00, "IPS_RequestAction(\$_IPS['TARGET'], 'CircuitDiagram', \$_IPS['ACTION']);"],
    ];

    /**
     * @var int Single Device
     */
    private const DEVICE_ONE = 0;

    /**
     * @var int Multiple Devices
     */
    private const DEVICE_MULTIPLE = 1;

    /**
     * @var int Minimum valid IPS Object ID
     */
    private const IPS_MIN_ID = 10000;

    /**
     * @var array<string,mixed> Textbox Presentation (Input)
     */
    private const TPS_PRESENTATION_TEXT = [
        'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
        'SUFFIX'       => '',
        'PREFIX'       => '',
        'MULTILINE'    => false,
    ];

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        // Socket(s)
        $this->RegisterPropertyInteger('DeviceNumber', 0);
        $this->RegisterPropertyInteger('SwitchVariable', 0);
        $this->RegisterPropertyString('SwitchVariables', '[]');
        $this->RegisterPropertyInteger('InventoryNumber', -1);
        $this->RegisterPropertyInteger('ScriptVariable', 0);
        // Schedule
        $this->RegisterPropertyInteger('EventVariable', 0);

        // Set visualization type to 1, as we want to offer HTML
        $this->SetVisualizationType(1);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        // Never delete this line!
        parent::Destroy();
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // number of devices
        $devices = $this->ReadPropertyInteger('DeviceNumber');
        $form['elements'][1]['items'][1]['visible'] = ($devices === self::DEVICE_ONE);
        $form['elements'][1]['items'][2]['visible'] = ($devices === self::DEVICE_MULTIPLE);
        // device list (set status column)
        $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
        foreach ($variables as $variable) {
            $form['elements'][1]['items'][2]['values'][] = [
                'Status' => $this->GetVariableStatus($variable['VariableID']),
            ];
        }

        // Extract Version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $form['actions'][0]['items'][2]['caption'] = sprintf('v%s.%d', $lib['Version'], $lib['Build']);

        // return form
        return json_encode($form);
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        // Never delete this line!
        parent::ApplyChanges();

        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        // Register references for variables
        $event = $this->ReadPropertyInteger('EventVariable');
        if ($event >= self::IPS_MIN_ID) {
            if (IPS_EventExists($event)) {
                $this->RegisterReference($event);
            } else {
                $this->LogDebug(__FUNCTION__, 'Event does not exist - Variable: ' . $event);
                $this->SetStatus(104);
                return;
            }
        }
        $script = $this->ReadPropertyInteger('ScriptVariable');
        if ($script >= self::IPS_MIN_ID) {
            if (IPS_ScriptExists($script)) {
                $this->RegisterReference($script);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script does not exist - Variable: ' . $script);
                $this->SetStatus(104);
                return;
            }
        }
        $devices = $this->ReadPropertyInteger('DeviceNumber');
        if ($devices == self::DEVICE_ONE) {
            $variable = $this->ReadPropertyInteger('SwitchVariable');
            if ($variable >= self::IPS_MIN_ID) {
                if (IPS_VariableExists($variable)) {
                    $this->RegisterReference($variable);
                } else {
                    $this->LogDebug(__FUNCTION__, 'Switch does not exist - Variable: ' . $variable);
                    $this->SetStatus(104);
                    return;
                }
            }
        } else {
            $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
            foreach ($variables as $variable) {
                if ($variable['VariableID'] >= self::IPS_MIN_ID) {
                    if (IPS_VariableExists($variable['VariableID'])) {
                        $this->RegisterReference($variable['VariableID']);
                        if ($this->GetVariableStatus($variable['VariableID']) != 'OK') {
                            $this->LogDebug(__FUNCTION__, 'Switch does not exist - Variables: ' . $variable['VariableID']);
                            $this->SetStatus(104);
                            return;
                        }
                    } else {
                        $this->LogDebug(__FUNCTION__, 'Switch does not exist - Variables: ' . $variable['VariableID']);
                        $this->SetStatus(104);
                        return;
                    }
                }
            }
        }

        // Register message update for variables
        if ($devices == self::DEVICE_ONE) {
            $variable = $this->ReadPropertyInteger('SwitchVariable');
            $this->RegisterMessage($variable, VM_UPDATE);
        } else {
            $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
            foreach ($variables as $variable) {
                $this->RegisterMessage($variable['VariableID'], VM_UPDATE);
            }
        }

        // Maintain variables
        $this->MaintainVariable('Label', $this->Translate('Labelling'), VARIABLETYPE_STRING, self::TPS_PRESENTATION_TEXT, 1, true);
        $this->EnableAction('Label');
        $this->SetValueString('Label', IPS_GetName($this->InstanceID));

        // Send a complete update message to the display, as parameters may have changed
        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());

        // Set status
        $this->SetStatus(102);
    }

    /**
     * The content of the function can be overwritten in order to carry out own reactions to certain messages.
     * The function is only called for registered MessageIDs/SenderIDs combinations.
     *
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     *
     * @param int   $timestamp Continuous counter timestamp
     * @param int   $sender    Sender ID
     * @param int   $message   ID of the message
     * @param array{0:mixed,1:bool,2:mixed,3:int} $data Data of the message
     * @return void
     */
    public function MessageSink(int $timestamp, int $sender, int $message, array $data): void
    {
        //$this->LogDebug(__FUNCTION__, 'SenderId: ' . $sender . ' Data: ' . $this->DebugPrint($data), 0);
        switch ($message) {
            case VM_UPDATE:
                // single or multiple
                $variable = 0;
                $devices = $this->ReadPropertyInteger('DeviceNumber');
                if ($devices == self::DEVICE_ONE) {
                    $variable = $this->ReadPropertyInteger('SwitchVariable');
                } else {
                    $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
                    foreach ($variables as $var) {
                        if ($var['VariableID'] == $sender) {
                            $variable = $var['VariableID'];
                            break;
                        }
                    }
                }
                if ($sender !== $variable) {
                    break;
                }
                // Check of change
                if ($data[0] == true && $data[1] == true) {
                    // Change on TRUE
                    $this->LogDebug(__FUNCTION__, 'OnChange on TRUE');
                    $this->ProcessSwitch($devices, $sender, true);
                } elseif ($data[0] == false && $data[1] == true) {
                    // Change on FALSE
                    $this->LogDebug(__FUNCTION__, 'OnChange on FALSE');
                    $this->ProcessSwitch($devices, $sender, false);
                } else {
                    // No change of status
                    //$this->LogDebug(__FUNCTION__, 'OnChange unchanged - status not changed');
                }
                break;
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
        // Ident == OnXxxxxYyyyy
        switch ($ident) {
            case 'CircuitDiagram':
                $this->ProcessSchedule($value);
                break;
            case 'Label':
                if (!empty($value)) {
                    $this->SetValueString($ident, $value);
                    IPS_SetName($this->InstanceID, $value);
                }
                break;
            case 'Button':
                $this->SwitchDevices($value);
                break;
            case 'OnDeviceNumber':
                $this->OnDeviceNumber($value);
                break;
            case 'OnCreateSchedule':
                $this->OnCreateSchedule($value);
                // No break. Add additional comment above this line if intentional!
            default:
                eval('$this->' . $ident . '(\'' . $value . '\');');
        }
    }

    /**
     * If the HTML-SDK is to be used, this function must be overwritten in order to return the HTML content.
     *
     * @return string Initial display of a representation via HTML SDK
     */
    public function GetVisualizationTile(): string
    {
        // Add a script to set the values when loading, analogous to changes at runtime
        // Although the return from GetFullUpdateMessage is already JSON-encoded, json_encode is still executed a second time
        // This adds quotation marks to the string and any quotation marks within it are escaped correctly
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';
        // Add static HTML from file
        $module = file_get_contents(__DIR__ . '/module.html');
        // Return everything
        // Important: $initialHandling at the end, as the handleMessage function is only defined in the HTML
        return $module . $initialHandling;
    }

    /**
     * Generate a message that updates all elements in the HTML display.
     *
     * @return string JSON encoded message information
     */
    private function GetFullUpdateMessage(): string
    {
        // dataset variable
        $schedule = 0;
        $state = 'off';
        // evaluate setup
        $event = $this->ReadPropertyInteger('EventVariable');
        if ($event >= self::IPS_MIN_ID) {
            if (IPS_EventExists($event)) {
                $data = $this->GetWeeklyScheduleInfo($event);
                $schedule = $data['WeekPlanActiv'];
            }
        }
        // One or more devices?
        $devices = $this->ReadPropertyInteger('DeviceNumber');
        if ($devices == self::DEVICE_ONE) {
            $variable = $this->ReadPropertyInteger('SwitchVariable');
            if (($variable >= self::IPS_MIN_ID) && (IPS_VariableExists($variable))) {
                $state = GetValueBoolean($variable);
            }
        } else {
            $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
            foreach ($variables as $variable) {
                if (($variable['VariableID'] >= self::IPS_MIN_ID) && (IPS_VariableExists($variable['VariableID']))) {
                    $state = GetValueBoolean($variable['VariableID']);
                    if ($state == true) break;
                }
            }
        }
        // Inventory number
        $number = $this->ReadPropertyInteger('InventoryNumber');
        // Data
        $result = [
            'state'    => ($state ? 'on' : 'off'),
            'schedule' => $schedule,
            'number'   => (($number > -1) ? sprintf('%02s', $number) : ''),
        ];
        return json_encode($result);
    }

    /**
     * Switch device(s) to the passed state.
     *
     * @param bool $state
     * @return void
     */
    private function SwitchDevices(bool $state): void
    {
        // One or more devices?
        $devices = $this->ReadPropertyInteger('DeviceNumber');
        if ($devices == self::DEVICE_ONE) {
            $variable = $this->ReadPropertyInteger('SwitchVariable');
            if (($variable >= self::IPS_MIN_ID) && (IPS_VariableExists($variable))) {
                if (HasAction($variable)) {
                    RequestAction($variable, $state);
                } else {
                    SetValueBoolean($variable, $state);
                }
            }
        } else {
            $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
            foreach ($variables as $variable) {
                if (($variable['VariableID'] >= self::IPS_MIN_ID) && (IPS_VariableExists($variable['VariableID']))) {
                    if (HasAction($variable['VariableID'])) {
                        RequestAction($variable['VariableID'], $state);
                    } else {
                        SetValueBoolean($variable['VariableID'], $state);
                    }
                }
            }
        }
    }

    /**
     * A schedule event occur and so switch the device(s)
     *
     * @param integer $value Action value (OFF=1, ON=2)
     * @return void
     */
    private function ProcessSchedule(int $value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        // Is Activate ON
        $state = false;
        if ($value == self::SCHEDULE_ON) {
            $state = true;
        }
        $this->SwitchDevices($state);
    }

    /**
     * Process changing switch state an send it to visualisation.
     *
     * @param int $devices witch group of devices (single or multiple)
     * @param int $device the triggering device
     * @param bool $state switch state (on or off)
     * @return void
     */
    private function ProcessSwitch(int $devices, int $device, bool $state): void
    {
        if (($devices == self::DEVICE_MULTIPLE) && ($state == false)) {
            $variables = json_decode($this->ReadPropertyString('SwitchVariables'), true);
            foreach ($variables as $variable) {
                $value = GetValueBoolean($variable['VariableID']);
                if ($value != $state) {
                    return;
                }
            }
        }
        $this->UpdateVisualizationValue(json_encode([
            'state' => ($state ? 'on' : 'off')
        ]));
    }

    /**
     * Received the status of a given variable
     *
     * @param int $vid variable ID.
     * @return string status message
     */
    private function GetVariableStatus($vid): string
    {
        if (!IPS_VariableExists($vid)) {
            return $this->Translate('Missing');
        } else {
            $var = IPS_GetVariable($vid);
            switch ($var['VariableType']) {
                case VARIABLETYPE_BOOLEAN:
                    if ($var['VariableCustomProfile'] != '') {
                        $profile = $var['VariableCustomProfile'];
                    } else {
                        $profile = $var['VariableProfile'];
                    }
                    if (!IPS_VariableProfileExists($profile)) {
                        return $this->Translate('Profile required');
                    }
                    if ($var['VariableCustomAction'] != 0) {
                        $action = $var['VariableCustomAction'];
                    } else {
                        $action = $var['VariableAction'];
                    }
                    if (!($action > self::IPS_MIN_ID)) {
                        return $this->Translate('Action required');
                    }
                    return 'OK';
                default:
                    return $this->Translate('Bool required');
            }
        }
    }

    /**
     * Creates a schedule plan.
     *
     * @param string $value instance ID.
     * @return void
     */
    private function OnCreateSchedule($value): void
    {
        $eid = $this->CreateWeeklySchedule($this->InstanceID, self::SCHEDULE_NAME, self::SCHEDULE_IDENT, self::SCHEDULE_SWITCH, -1);
        if ($eid >= self::IPS_MIN_ID) {
            $this->UpdateFormField('EventVariable', 'value', $eid);
        }
    }

    /**
     * User has select an new number of devices.
     *
     * @param string $value select value.
     * @return void
     */
    private function OnDeviceNumber($value): void
    {
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value);
        $this->UpdateFormField('SwitchVariable', 'visible', ($value == self::DEVICE_ONE));
        $this->UpdateFormField('SwitchVariables', 'visible', ($value == self::DEVICE_MULTIPLE));
    }
}

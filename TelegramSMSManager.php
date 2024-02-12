<?php
require_once __DIR__ . '/vendor/autoload.php';

use Telegram\Bot\Api;

class TelegramSMSManager
{
    private $telegram;
    private $configFile = 'telegram_sms_config.json';

    public function __construct()
    {
        $this->telegram = null;
    }

    public function loadConfig()
    {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $config;
            }
        }
        return [];
    }

    public function saveConfig($config)
    {
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    public function configureTelegramBot()
    {
        if (!$this->telegram) {
            $this->initializeTelegramBot();
        }

        echo "Welcome to the Telegram SMS Manager!\n";
        echo "1. Configure Telegram Bot Token\n";
        echo "2. Send Test Message\n";
        echo "3. Manage Contacts\n";
        echo "4. Logout\n";
        echo "5. Exit\n";

        $choice = readline("Enter your choice (1/2/3/4/5): ");

        switch ($choice) {
            case '1':
                $this->configureToken();
                break;
            case '2':
                $this->sendTestMessage();
                break;
            case '3':
                $this->manageContacts();
                break;
            case '4':
                $this->telegram = null;
                $this->configureTelegramBot();
                break;
            case '5':
                exit("Goodbye!\n");
            default:
                echo "Invalid choice. Please try again.\n";
                $this->configureTelegramBot();
        }
    }

    private function initializeTelegramBot()
    {
        $config = $this->loadConfig();
        if (!empty($config['token'])) {
            $this->telegram = new Api($config['token']);
        }
    }

    private function loggedInMenu()
    {
        echo "1. View Configuration\n";
        echo "2. Change Telegram Bot Token\n";
        echo "3. Send Test Message\n";
        echo "4. Manage Contacts\n";
        echo "5. Logout\n";
        echo "6. Exit\n";

        $choice = readline("Enter your choice (1/2/3/4/5/6): ");

        switch ($choice) {
            case '1':
                $this->viewConfiguration();
                break;
            case '2':
                $this->configureToken();
                break;
            case '3':
                $this->sendTestMessage();
                break;
            case '4':
                $this->manageContacts();
                break;
            case '5':
                $this->telegram = null;
                $this->configureTelegramBot();
                break;
            case '6':
                exit("Goodbye!\n");
            default:
                echo "Invalid choice. Please try again.\n";
                $this->loggedInMenu();
        }
    }

    private function viewConfiguration()
    {
        $config = $this->loadConfig();
        echo "Current Configuration:\n";
        echo "Telegram Bot Token: " . ($config['token'] ?? 'Not configured') . "\n";
        echo "Phone Number: " . ($config['phone_number'] ?? 'Not configured') . "\n";
        echo "Default Message: " . ($config['message'] ?? 'Not configured') . "\n";
        echo "Proxy URL: " . ($config['proxy'] ?? 'Not configured') . "\n";
        echo "\n";
        $this->loggedInMenu();
    }

    private function configureToken()
    {
        $token = readline("Enter your Telegram Bot Token: ");
        $config = $this->loadConfig();
        $config['token'] = $token;
        $this->saveConfig($config);
        echo "Telegram Bot Token configured successfully!\n";
        $this->loggedInMenu();
    }

    private function sendTestMessage()
    {
        $config = $this->loadConfig();
        if (empty($config['token'])) {
            echo "Error: Telegram Bot Token is not configured. Please configure it first.\n";
            $this->loggedInMenu();
        }

        $phone_number = readline("Enter the phone number: ");
        $message = readline("Enter the test message: ");
        $proxy = readline("Enter the proxy URL (if any): ");

        $result = $this->sendTelegramSMS($phone_number, $message, $config['token'], $proxy);
        echo $result . "\n";

        $this->loggedInMenu();
    }

    private function sendTelegramSMS($phone_number, $message, $token, $proxy = '')
    {
        if (!$this->telegram) {
            $this->initializeTelegramBot();
        }

        if (!empty($proxy)) {
            $this->telegram->addCommand(
                'setWebhook',
                [
                    'url' => $proxy,
                ]
            );
        }

        try {
            $this->telegram->sendMessage([
                'chat_id' => $phone_number,
                'text' => $message,
            ]);
            return 'Message sent successfully!';
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            return 'Error sending message: ' . $e->getMessage();
        }
    }

    private function manageContacts()
    {
        $config = $this->loadConfig();

        echo "1. View Contacts\n";
        echo "2. Add Contact\n";
        echo "3. Remove Contact\n";
        echo "4. Back to Menu\n";

        $choice = readline("Enter your choice (1/2/3/4): ");

        switch ($choice) {
            case '1':
                $this->viewContacts();
                break;
            case '2':
                $this->addContact();
                break;
            case '3':
                $this->removeContact();
                break;
            case '4':
                $this->loggedInMenu();
                break;
            default:
                echo "Invalid choice. Please try again.\n";
                $this->manageContacts();
        }
    }

    private function viewContacts()
    {
        $config = $this->loadConfig();

        if (!empty($config['contacts'])) {
            echo "Contacts:\n";
            foreach ($config['contacts'] as $index => $contact) {
                echo "$index. $contact\n";
            }
        } else {
            echo "No contacts found.\n";
        }

        echo "\n";
        $this->manageContacts();
    }

    private function addContact()
    {
        $contact = readline("Enter the phone number to add: ");
        $config = $this->loadConfig();

        if (!isset($config['contacts'])) {
            $config['contacts'] = [];
        }

        $config['contacts'][] = $contact;
        $this->saveConfig($config);

        echo "Contact added successfully!\n";
        $this->manageContacts();
    }

    private function removeContact()
    {
        $config = $this->loadConfig();

        if (empty($config['contacts'])) {
            echo "No contacts found.\n";
            $this->manageContacts();
        }

        $this->viewContacts();

        $index = readline("Enter the index of the contact to remove: ");

        if (isset($config['contacts'][$index])) {
            unset($config['contacts'][$index]);
            $config['contacts'] = array_values($config['contacts']); // Reindex the array
            $this->saveConfig($config);
            echo "Contact removed successfully!\n";
        } else {
            echo "Invalid index.\n";
        }

        $this->manageContacts();
    }
}

$manager = new TelegramSMSManager();
$manager->configureTelegramBot();

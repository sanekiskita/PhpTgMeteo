<?php

namespace App\Services;

use App\Interfaces\MessageHandlerInterface;
use App\Interfaces\CallbackHandlerInterface;
use App\Factories\HandlerFactory;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TelegramService
{
    private Telegram $telegram;
    private array $config;
    private Logger $logger;
    private MessageHandlerInterface $messageHandler;
    private CallbackHandlerInterface $callbackHandler;
    private array $ALLOWED_UPDATES = [
        Update::TYPE_MESSAGE,
        Update::TYPE_CALLBACK_QUERY,
    ];

    public function __construct(array $config, HandlerFactory $handlerFactory, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initializeTelegram();
        $this->messageHandler = $handlerFactory->createMessageHandler();
        $this->callbackHandler = $handlerFactory->createCallbackHandler();
    }

    private function initializeTelegram(): void
    {
        try {
            if (!$this->config["telegram"]["bot_token"] || !$this->config["telegram"]["bot_username"]) {
                throw new TelegramException("Bot token or username is not set");
            }

            $this->telegram = new Telegram(
                $this->config["telegram"]["bot_token"],
                $this->config["telegram"]["bot_username"]
            );

            $this->setupCommands();
            $this->logger->info("Telegram bot initialized successfully");

        } catch (TelegramException $e) {
            throw new TelegramException("Failed to initialize Telegram bot: " . $e->getMessage());
        }
    }

    private function setupCommands(): void
    {

        $commands = [
            ["command" => "start", "description" => "Начать работу с ботом"],
            ["command" => "weather", "description" => "Показать меню выбора города"],
            ["command" => "help", "description" => "Показать справку"]
        ];

        $result = Request::setMyCommands([
            "commands" => json_encode($commands)
        ]);

        if (!$result->isOk()) {
            $this->logger->warning("Failed to set commands: " . $result->getDescription());
        } else {
            $this->logger->info("Bot commands set successfully");
        }
    }

    public function run(): void
    {
        try {
            Request::deleteWebhook();
            if ($this->shouldUseWebhook()) {
                $this->runWebhook();
            } else {
                $this->runPolling();
            }
        } catch (TelegramException $e) {
            $this->logger->error("Telegram bot error: " . $e->getMessage());
            throw $e;
        }
    }

    private function shouldUseWebhook(): bool
    {
        return $this->config["telegram"]["webhook_url"] !== NULL;
    }

    /**
     * Запуск бота в режиме webhook
     */
    private function runWebhook(): void
    {
        $this->logger->info("Starting bot in webhook mode");
        
        $result = $this->telegram->setWebhook(
            $this->config["telegram"]["webhook_url"],
            ["allowed_updates" => $this->ALLOWED_UPDATES]
        );

        if (!$result->isOk()) {
            throw new TelegramException("Failed to set webhook: " . $result->getDescription());
        }

        $this->telegram->handle();
    }

    /**
     * Запуск бота в режиме polling
     */
    private function runPolling(): void
    {
        $this->logger->info("Starting bot in polling mode");
        
        $offset = 0;

        while (true) {
            try {
                $response = Request::getUpdates([
                    "offset" => $offset,
                    "limit" => 10,
                    "timeout" => 30,
                    "allowed_updates" => $this->ALLOWED_UPDATES
                ]);

                if ($response->isOk()) {
                    $updates = $response->getResult();
                    
                    if (count($updates) > 0) {
                        foreach ($updates as $update) {
                            $offset = $update->getUpdateId() + 1;
                            $this->processUpdate($update);
                        }
                    }
                }
                
                sleep(1);
            } catch (\Exception $e) {
                $this->logger->error("Polling error: " . $e->getMessage());
                sleep(5);
            }
        }
    }

    public function processUpdate($update): void
    {
        try {
            if ($update->getMessage()) {
                $this->messageHandler->handleMessage($update->getMessage());
            }
            
            if ($update->getCallbackQuery()) {
                $this->callbackHandler->handleCallback($update->getCallbackQuery());
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Error processing update: " . $e->getMessage());
        }
    }

    public function getTelegram(): Telegram
    {
        return $this->telegram;
    }
} 
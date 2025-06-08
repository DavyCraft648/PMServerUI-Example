<?php
declare(strict_types=1);

namespace DavyCraft648\UIExample;

use DavyCraft648\PMServerUI\ActionFormData;
use DavyCraft648\PMServerUI\ActionFormResponse;
use DavyCraft648\PMServerUI\FormCancelationReason;
use DavyCraft648\PMServerUI\MessageFormData;
use DavyCraft648\PMServerUI\MessageFormResponse;
use DavyCraft648\PMServerUI\ModalFormData;
use DavyCraft648\PMServerUI\ModalFormResponse;
use DavyCraft648\PMServerUI\PMServerUI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use function json_encode;

class UIExample extends PluginBase implements Listener{

	protected function onEnable(): void{
		PMServerUI::register($this, true);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if(!$sender instanceof Player){
			return true;
		}
		$this->showForm($sender, $args[0] ?? "");
		return true;
	}

	private function showForm(Player $player, string $type): void{
		switch($type){
			case "0":
			case "showActionForm":
				$this->showActionForm($player);
				break;
			case "1":
			case "showBasicMessageForm":
				$this->showBasicMessageForm($player);
				break;
			case "2":
			case "showTranslatedMessageForm":
				$this->showTranslatedMessageForm($player);
				break;
			case "3":
			case "showBasicModalForm":
				$this->showBasicModalForm($player);
				break;
			default:
				ActionFormData::create()
					->title("Form Example")
					->body("Select example:")
					->button("showActionForm")
					->button("showBasicMessageForm")
					->button("showTranslatedMessageForm")
					->button("showBasicModalForm")
					
					->show($player)->then(function(Player $player, ActionFormResponse $response): void{
						if($response->canceled || $response->selection === null){
							return;
						}
						$this->showForm($player, (string)$response->selection);
					});
		}
	}

	/**
	 * Shows a very basic action form.
	 */
	public function showActionForm(Player $player): void{
		$form = ActionFormData::create()
			->title("Test Title")
			->body("Body text here!")
			->button("Button 1")
			->header("Header")
			->divider()
			->button("Button with local image", "textures/ui/icon_bell")
			->label("Label")
			->divider()
			->button("Another button", "https://github.com/Mojang/bedrock-samples/blob/main/resource_pack/textures/ui/Add-Ons_Nav_Icon36x36.png?raw=true", "url");

		$form->show($player)
			->then(function(Player $player, ActionFormResponse $result): void{
				if($result->canceled){
					$player->sendMessage("Player exited out of the dialog. Note that if the chat window is up, dialogs are automatically canceled.");
				}else{
					$player->sendMessage("Your result was: " . $result->selection);
				}
			})
			->catch(function(\Throwable $t): void{
				$this->getLogger()->notice("Hey!! {$t->getMessage()}");
			});
	}

	/**
	 * Shows an example two-button dialog.
	 */
	public function showBasicMessageForm(Player $player): void{
		$messageForm = MessageFormData::create()
			->title("Message Form Example")
			->body("This shows a simple example using §o§7MessageFormData§r.")
			->button1("Button 1")
			->button2("Button 2");

		$messageForm->show($player)
			->then(function(Player $player, MessageFormResponse $formData): void{
				// player canceled the form, or another dialog was up and open.
				if($formData->canceled || $formData->selection === null){
					return;
				}

				$player->sendMessage("You selected " . ($formData->selection === 0 ? "Button 1" : "Button 2"));
			})
			->catch(function(\Throwable $t): void{
				$this->getLogger()->notice("Failed to show form: " . $t->getMessage());
			});
	}

	/**
	 * Shows an example translated two-button dialog.
	 */
	public function showTranslatedMessageForm(Player $player): void{
		$messageForm = MessageFormData::create()
			->title(new Translatable("permissions.removeplayer"))
			->body(new Translatable("accessibility.list.or.two", ["Player 1", "Player 2"]))
			->button1("Player 1")
			->button2("Player 2");

		$messageForm->show($player)
			->then(function(Player $player, MessageFormResponse $formData): void{
				// player canceled the form, or another dialog was up and open.
				if($formData->canceled || $formData->selection === null){
					return;
				}

				$player->sendMessage("You selected " . ($formData->selection === 0 ? "Player 1" : "Player 2"));
			})
			->catch(function(\Throwable $t): void{
				$this->getLogger()->notice("Failed to show form: " . $t->getMessage());
			});
	}

	/**
	 * Shows an example multiple-control modal dialog.
	 */
	public function showBasicModalForm(Player $player): void{
		$modalForm = ModalFormData::create()->title("Example Modal Controls for §o§7ModalFormData§r");

		$modalForm->toggle("Toggle");
		$modalForm->toggle("Toggle, default", default: true);
		$modalForm->toggle("Toggle, default, tooltip", default: true, tooltip: "Hovered");

		$modalForm->header("Header");
		$modalForm->divider();

		$modalForm->slider("Slider", 0, 50);
		$modalForm->slider("Slider, custom step", 0, 50, step: 5);
		$modalForm->slider("Slider, default, custom step", 0, 50, step: 5, default: 30);
		$modalForm->slider("Slider, default, tooltip, custom step", 0, 50, step: 5, default: 30, tooltip: "Hi");

		$modalForm->label("hi!!");
		$modalForm->divider();

		$modalForm->dropdown("Dropdown", ["option 1", "option 2", "option 3"]);
		$modalForm->dropdown("Dropdown, tooltip", ["option 1", "option 2", "option 3"], tooltip: "Cool");
		$modalForm->dropdown("Dropdown, default", ["option 1", "option 2", "option 3"], default: 2);

		$modalForm->divider();

		$modalForm->textField("Input");
		$modalForm->textField("Input", "type text here");
		$modalForm->textField("Input, default", "type text here", default: "this is default");
		$modalForm->textField("Input, default, tooltip", "type text here", default: "this is default", tooltip: "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.");

		$modalForm->submitButton("Send ^-^");

		$modalForm->show($player)
			->then(function(Player $player, ModalFormResponse $formData): void{
				$player->sendMessage("Modal form results: " . json_encode($formData->formValues, JSON_UNESCAPED_SLASHES, 2));
			})
			->catch(function(\Throwable $t): void{
				$this->getLogger()->notice("Failed to show form: {$t->getMessage()}");
			});
	}

	/**
	 * Sends the form to the player and try again if the player still has the chat open.
	 */
	public function deferShowForm(Player $player, int $attempt = 0): void{
		ActionFormData::create()
			->body("Yeyy!! Happy to see you now")

			->show($player)->then(function(Player $player, ActionFormResponse $response) use ($attempt): void{
				if($response->canceled && $response->cancelationReason === FormCancelationReason::UserBusy){
					if($attempt >= 8){
						$player->sendMessage("Ugh... we already tried to send you form 8 times");
						return;
					}
					$player->sendMessage("Hey!! Please close your chat ui, we want to send you a form! (attempt $attempt)");
					$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($attempt, $player): void{
						if($player->isConnected()){
							$this->deferShowForm($player, $attempt + 1);
						}
					}), 20);
				}
			});
	}

	public function omPlayerChat(PlayerChatEvent $event): void{
		if($event->getMessage() === "ui test"){
			$this->showActionForm($event->getPlayer());
		}elseif($event->getMessage() === "ui test2"){
			$this->deferShowForm($event->getPlayer());
		}
	}
}
<?php

namespace yulla1234\TagMaster;

class TagMaster extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener {

  protected $mode = ['create' => [], 'delete' => [], 'string' => []];
  protected $config, $tag;

  public function onEnable () {
    $this->config = new \pocketmine\utils\Config ($this->getDataFolder() . 'TagMaster.yml', \pocketmine\utils\Config::YAML);
    $this->tag = $this->config->getAll ();
    $this->getServer()->getPluginManager()->registerEvents ($this, $this);
    $this->getServer()->getCommandMap()->register ('TagMaster', new CLASS ($this) extends \pocketmine\command\Command {

      public function __construct (TagMaster $plugin) {
        $this->plugin = $plugin;
        parent::__construct ('태그', 'TagMaster || Made by yulla1234', '태그', ['tag', 'tagmaster']);
      }

      public function sendMsg ($sender, string $message) {
        $sender->sendMessage ('§l§b태그 ||§r ' . $message);
      }

      public function execute (\pocketmine\command\CommandSender $sender, string $commandLabel, array $args) {
        if (!$sender instanceof \pocketmine\Player) {
          $this->sendMsg ($sender, '인게임에서 입력하여주십시오.');
          return true;
        }
        if (!$sender->isOp ()) {
          $this->sendMsg ($sender, '당신은 권한이 없습니다.');
          return true;
        }
        if (!isset ($args[0])) {
          $this->sendMsg ($sender, '/태그 생성 || 태그를 생성합니다.');
          $this->sendMsg ($sender, '/태그 제거 || 태그를 제거합니다.');
          $this->sendMsg ($sender, '/태그 목록 || 태그 목록을 출력합니다.');
          return true;
        }
        switch ($args[0]) {
          case '생성' :
          case '추가' :
          case 'add' :
          case 'create' :
          if ($this->plugin->checkMode ($sender->getName (), 'all')) {
            if ($this->plugin->checkMode ($sender->getName (), 'delete')) {
              $this->sendMsg ($sender, '제거 작업을 중단하여주십시오.');
            }else{
              $this->plugin->sendMode ($sender->getName (), 'create', false);
              $this->sendMsg ($sender, '생성 작업을 중단하셨습니다.');
            }
            return true;
          }
          $this->plugin->sendMode ($sender->getName (), 'create', true);
          $this->sendMsg ($sender, '생성 작업을 시작하셨습니다. [블럭을 부숴주십시오.]');
          break;

          case '삭제' :
          case '제거' :
          case 'remove' :
          case 'delete' :
          if ($this->plugin->checkMode ($sender->getName (), 'all')) {
            if ($this->plugin->checkMode ($sender->getName (), 'create')) {
              $this->sendMsg ($sender, '생성 작업을 중단하여주십시오.');
            }else{
              $this->plugin->sendMode ($sender->getName (), 'delete', false);
              $this->sendMsg ($sender, '제거 작업을 중단하셨습니다.');
            }
            return true;
          }
          $this->plugin->sendMode ($sender->getName (), 'delete', true);
          $this->sendMsg ($sender, '제거 작업을 시작하셨습니다. [블럭을 부숴주십시오.]');
          break;

          case '목록' :
          case '리스트' :
          case 'list' :
          $index = 0;
          foreach ($this->plugin->tagList () as $loc => $string) {
            $index ++;
            $this->sendMsg ($sender, '§l§b[§f' . $index . '§b]§r 좌표: ' . $loc . ', 문자열: ' . $string);
          }
          if ($index === 0) {
            $this->sendMsg ($sender, '현재 태그가 존재하지 않습니다.');
          }else{
            $this->sendMsg ($sender, '총 ' . $index . '개의 태그가 존재합니다.');
          }
          break;
        }
      }
    });
  }

  public function onSave () {
    $this->config->setAll ($this->tag);
		$this->config->save ();
  }

  public function sendMsg ($player, string $message) {
    $player->sendMessage ('§l§b태그 ||§r ' . $message);
  }

  public function sendMode (string $player, $mode = 'create', $force = true) {
    if ($mode === 'create') {
      if (!$force) {
        if (!$this->checkMode ($player, 'create')) return true;
        unset ($this->mode ['create'] [$player]);
      }else{
        if ($this->checkMode ($player, 'create')) return true;
        $this->mode ['create'] [$player] = [];
      }
    }elseif ($mode === 'delete') {
      if (!$force) {
        if (!$this->checkMode ($player, 'delete')) return true;
        unset ($this->mode ['delete'] [$player]);
      }else{
        if ($this->checkMode ($player, 'delete')) return true;
        $this->mode ['delete'] [$player] = [];
      }
    }
  }

  public function checkMode (string $player, $mode = 'all') {
    switch ($mode) {
      case 'all' :
      case '모두' :
      if (isset ($this->mode ['create'] [$player]) or isset ($this->mode ['delete'] [$player])) {
        return true;
      }
      return false;
      break;

      case 'create' :
      case '생성자' :
      if (isset ($this->mode ['create'] [$player])) {
        return true;
      }
      return false;
      break;

      case 'delete' :
      case '제거' :
      if (isset ($this->mode ['delete'] [$player])) {
        return true;
      }
      return false;
      break;
    }
  }

  public function tagList () {
    $tags = [];
    foreach ($this->tag as $loc => $string) {
      $tags[$loc] = $string;
    }
    return $tags;
  }

  public function onBlockBreak (\pocketmine\event\block\BlockBreakEvent $e) {
    $player = $e->getPlayer ();
    $block = $e->getBlock ();
    $loc = $block->x . ':' . ($block->y + 1) . ':' . $block->z . ':' . $block->level->getFolderName ();
    if ($this->checkMode ($player->getName (), 'create')) {
      $pk = new \pocketmine\network\mcpe\protocol\ModalFormRequestPacket ();
      $pk->formId = 0111;
      $pk->formData = json_encode ([
        'type' => 'custom_form',
        'title' => '§l§b태그 생성 ||',
        'content' => [
          [
            'type' => 'input',
            'text' => '§l§b|| §f원하시는 태그를 작성하여주십시오.' . "\n" . '§l§b||§f 줄내림은 [n] 으로 가능합니다.',
            'placeholder' => '§l§b|| §f입력란'
          ]
        ]
      ]);
      $player->sendDataPacket ($pk);
      $this->mode ['string'] [$player->getName ()] = $loc;
      $e->setCancelled (true);
    }elseif ($this->checkMode ($player->getName (), 'delete')) {
      if (isset ($this->tag [$loc])) {
        $this->sendMsg ($player, '해당 위치에 태그를 제거하셨습니다.');
        $this->sendMsg ($player, '해당 사항은 재접속 후에 적용됩니다.');
        unset ($this->tag [$loc]);
        $this->onSave ();
        $e->setCancelled (true);
      }else{
        $this->sendMsg ($player, '해당 위치에는 태그가 존재하지 않습니다.');
      }
    }
  }

  public function onPlayerJoin (\pocketmine\event\player\PlayerJoinEvent $e) {
    $player = $e->getPlayer ();
    foreach ($this->tag as $loc => $string) {
      $loc = explode (':', $loc);
      if (!is_null (($level = $this->getServer()->getLevelByName ($loc[3])))) {
        $pos = new \pocketmine\level\Position ($loc[0] + 0.5, $loc[1], $loc[2] + 0.5, $level);
        $player->level->addParticle (new \pocketmine\level\particle\FloatingTextParticle ($pos->asVector3 (), str_replace ('[n]', "\n", $string)));
      }
    }
  }

  public function onDataPacketReceive (\pocketmine\event\server\DataPacketReceiveEvent $e) {
    $pk = $e->getPacket ();
    $player = $e->getPlayer ();
    if ($pk instanceof \pocketmine\network\mcpe\protocol\ModalFormResponsePacket) {
      $value = json_decode ($pk->formData, true)[0];
      if ($pk->formId === 0111) {
        if (is_null ($value)) {
          unset ($this->mode ['string'] [$player->getName ()]);
          $this->sendMsg ($player, '모든 항목을 입력하여주십시오.');
          return true;
        }
        $this->sendMsg ($player, '태그를 생성하셨습니다.');
        $this->sendMsg ($player, '* 좌표: ' . $this->mode ['string'] [$player->getName ()]);
        $this->sendMsg ($player, '* 문자열: ' . $value);
        $this->tag [$this->mode ['string'] [$player->getName ()]] = $value;
        $this->onSave ();
        $loc = explode (':', $this->mode ['string'] [$player->getName ()]);
        $pos = new \pocketmine\level\Position ($loc[0] + 0.5, $loc[1], $loc[2] + 0.5, $player->level);
        $player->level->addParticle (new \pocketmine\level\particle\FloatingTextParticle ($pos->asVector3 (), str_replace ('[n]', "\n", $value)));
      }
    }
  }
}

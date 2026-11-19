<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

/**
 * Un équipement Groupir est un groupe d'équipements (ex. "Ampoules salon").
 *  - configuration.commands : commandes du groupe, chacune étant un sous-groupe d'équipements :
 *      type "info"   : {id, type, name, rule, ruleCount, valueTrue, valueFalse,
 *                       members: [{eqLogic_id, info_id, operator, value} | {type: "scenario", scenario_id, scenarioState}]}
 *                      => info "status::<id>" : valueTrue (défaut "ok") si la règle est respectée
 *                         (rule : "all" tous, "any" au moins un, "atleast" au moins ruleCount), sinon valueFalse (défaut "ko")
 *      type "action" : {id, type, name, pause (secondes entre deux actions), members: [{eqLogic_id, actions: [{action_id, actionValue}]}
 *                                                 | {type: "scenario", scenario_id, scenarioAction}]}
 *                      => action "action::<id>" : exécute les actions de chaque équipement, dans l'ordre
 *                         (ex. On, puis Luminosité 80, puis Couleur)
 */
class groupir extends eqLogic {

  /*     * ***********************Méthodes statiques*************************** */

  /** Callback du listener : une commande info surveillée vient de changer, on recalcule le groupe. */
  public static function pull($_option) {
    $eqLogic = self::byId($_option['eqLogic_id']);
    if (is_object($eqLogic) && $eqLogic->getIsEnable()) {
      $eqLogic->refreshState(isset($_option['event_id']) ? $_option['event_id'] : null);
    }
  }

  /** Extrait l'id d'une commande stockée sous la forme #123#. */
  public static function extractCmdId($_expression) {
    if (preg_match('/^#(\d+)#$/', trim((string) $_expression), $matches)) {
      return $matches[1];
    }
    return null;
  }

  /** Remplace une référence de commande (#123#) par sa valeur courante. */
  public static function resolveValue($_value) {
    $cmdId = self::extractCmdId($_value);
    if ($cmdId === null) {
      return $_value;
    }
    $cmd = cmd::byId($cmdId);
    return is_object($cmd) ? $cmd->execCmd() : null;
  }

  /** Compare deux valeurs (numériquement si les deux sont des nombres, sinon en texte insensible à la casse). */
  public static function compare($_left, $_operator, $_right) {
    if (is_numeric($_left) && is_numeric($_right)) {
      $_left = floatval($_left);
      $_right = floatval($_right);
    } else {
      $_left = strtolower(trim((string) $_left));
      $_right = strtolower(trim((string) $_right));
    }
    switch ($_operator) {
      case '==':
        return $_left == $_right;
      case '!=':
        return $_left != $_right;
      case '>':
        return $_left > $_right;
      case '>=':
        return $_left >= $_right;
      case '<':
        return $_left < $_right;
      case '<=':
        return $_left <= $_right;
      case 'contains':
        return strpos((string) $_left, (string) $_right) !== false;
      case 'not_contains':
        return strpos((string) $_left, (string) $_right) === false;
    }
    return false;
  }

  /*     * *********************Méthodes d'instance************************* */

  /** Commandes du groupe (configuration "commands"). */
  public function getGroupCommands() {
    return $this->getArrayConfiguration('commands');
  }

  /** Vérifie les commandes d'un groupe : nom obligatoire et unique, identifiant stable, valeurs par défaut. */
  public function preSave() {
    $reserved = array(strtolower(__('Rafraîchir', __FILE__)));
    $names = array();
    $commands = array();
    foreach ($this->getGroupCommands() as $command) {
      $command['name'] = trim($command['name'] ?? '');
      if ($command['name'] == '') {
        throw new Exception(__('Chaque commande du groupe doit avoir un nom', __FILE__));
      }
      $key = strtolower($command['name']);
      if (in_array($key, $reserved)) {
        throw new Exception(__('Nom de commande réservé', __FILE__) . ' : ' . $command['name']);
      }
      if (in_array($key, $names)) {
        throw new Exception(__('Deux commandes portent le même nom', __FILE__) . ' : ' . $command['name']);
      }
      $names[] = $key;
      if (!preg_match('/^[a-zA-Z0-9_]+$/', $command['id'] ?? '')) {
        $command['id'] = uniqid();
      }
      $command['type'] = (($command['type'] ?? '') == 'action') ? 'action' : 'info';
      if ($command['type'] == 'info') {
        foreach (array('valueTrue' => 'ok', 'valueFalse' => 'ko') as $field => $default) {
          $command[$field] = trim($command[$field] ?? '');
          if ($command[$field] == '') {
            $command[$field] = $default;
          }
        }
        $command['rule'] = in_array($command['rule'] ?? '', array('all', 'any', 'atleast')) ? $command['rule'] : 'all';
        $command['ruleCount'] = max(1, intval($command['ruleCount'] ?? 1));
        unset($command['pause']);
      } else {
        // Pause entre deux actions : 0 à 30 secondes
        $pause = floatval(str_replace(',', '.', (string) ($command['pause'] ?? 0)));
        $command['pause'] = max(0, min(30, $pause));
        unset($command['valueTrue'], $command['valueFalse'], $command['rule'], $command['ruleCount']);
      }
      $command['members'] = $this->normalizeMembers($command);
      $commands[] = $command;
    }
    $this->setConfiguration('commands', $commands);
  }

  /**
   * Vérifie et nettoie les lignes d'une commande du groupe.
   * Lève une exception claire (commande, ligne) si une ligne est incomplète ou fait référence à un élément supprimé.
   */
  private function normalizeMembers($_command) {
    $isAction = ($_command['type'] == 'action');
    $members = array();
    $seen = array();
    $line = 0;
    foreach ($_command['members'] ?? array() as $member) {
      if (!is_array($member)) {
        continue;
      }
      $line++;
      $where = __('Commande', __FILE__) . ' « ' . $_command['name'] . ' », ' . __('ligne', __FILE__) . ' ' . $line . ' : ';
      // Scénario : action (lancer, arrêter, activer, désactiver) ou état (actif, inactif, en cours, arrêté)
      if (($member['type'] ?? '') == 'scenario') {
        $scenario = is_numeric($member['scenario_id'] ?? '') ? scenario::byId($member['scenario_id']) : null;
        if (!is_object($scenario)) {
          throw new Exception($where . (($member['scenario_id'] ?? '') == '' ? __('choisissez un scénario ou retirez la ligne', __FILE__) : __('le scénario a été supprimé, choisissez-en un autre ou retirez la ligne', __FILE__)));
        }
        if ($isAction) {
          $action = $member['scenarioAction'] ?? 'start';
          $members[] = array('type' => 'scenario', 'scenario_id' => $scenario->getId(), 'scenarioAction' => in_array($action, array('start', 'stop', 'activate', 'deactivate')) ? $action : 'start');
        } else {
          $state = $member['scenarioState'] ?? 'active';
          $members[] = array('type' => 'scenario', 'scenario_id' => $scenario->getId(), 'scenarioState' => in_array($state, array('active', 'inactive', 'running', 'stopped')) ? $state : 'active');
        }
        continue;
      }
      // Équipement
      $eqId = $member['eqLogic_id'] ?? '';
      if (!is_numeric($eqId)) {
        throw new Exception($where . __('choisissez un équipement ou retirez la ligne', __FILE__));
      }
      if ($eqId == $this->getId()) {
        throw new Exception($where . __('un groupe ne peut pas se contenir lui-même', __FILE__));
      }
      if (!is_object(eqLogic::byId($eqId))) {
        throw new Exception($where . __('l\'équipement a été supprimé, choisissez-en un autre ou retirez la ligne', __FILE__));
      }
      if (in_array($eqId, $seen)) {
        throw new Exception($where . __('cet équipement est déjà présent dans la commande', __FILE__));
      }
      $seen[] = $eqId;
      if (!$isAction) {
        $this->checkMemberCmd($where, $eqId, $member['info_id'] ?? '', 'info');
        $operator = $member['operator'] ?? '==';
        $members[] = array(
          'eqLogic_id' => $eqId,
          'info_id' => $member['info_id'],
          'operator' => in_array($operator, array('==', '!=', '>', '>=', '<', '<=', 'contains', 'not_contains')) ? $operator : '==',
          'value' => (string) ($member['value'] ?? ''),
        );
        continue;
      }
      $list = (isset($member['actions']) && is_array($member['actions'])) ? $member['actions'] : array($member);
      $actions = array();
      foreach (array_values($list) as $k => $action) {
        $this->checkMemberCmd($where . __('action', __FILE__) . ' ' . ($k + 1) . ' : ', $eqId, is_array($action) ? ($action['action_id'] ?? '') : '', 'action');
        $actions[] = array('action_id' => $action['action_id'], 'actionValue' => (string) ($action['actionValue'] ?? ''));
      }
      if (count($actions) == 0) {
        throw new Exception($where . __('ajoutez au moins une commande action', __FILE__));
      }
      $members[] = array('eqLogic_id' => $eqId, 'actions' => $actions);
    }
    if (count($members) == 0) {
      throw new Exception(__('Commande', __FILE__) . ' « ' . $_command['name'] . ' » : ' . __('ajoutez au moins un équipement ou un scénario, ou supprimez la commande', __FILE__));
    }
    return $members;
  }

  /** Vérifie qu'une commande info / action choisie existe et appartient bien à l'équipement de la ligne. */
  private function checkMemberCmd($_where, $_eqId, $_cmdId, $_type) {
    if (!is_numeric($_cmdId)) {
      throw new Exception($_where . ($_type == 'info' ? __('choisissez la commande info', __FILE__) : __('choisissez la commande action', __FILE__)));
    }
    $cmd = cmd::byId($_cmdId);
    if (!is_object($cmd) || $cmd->getType() != $_type || $cmd->getEqLogic_id() != $_eqId) {
      throw new Exception($_where . __('la commande a été supprimée, choisissez-en une autre', __FILE__));
    }
  }

  /** Crée "Rafraîchir" puis une commande info ou action par commande du groupe (nom et valeurs définis par l'utilisateur). */
  public function postSave() {
    // Commandes attendues
    $expected = array();
    foreach ($this->getGroupCommands() as $command) {
      if (($command['type'] ?? 'info') == 'action') {
        $expected['action::' . $command['id']] = array($command['name'], 'action', 'other');
      } else {
        // Numérique si les deux valeurs le sont (ex. 1 / 0), texte sinon
        $numeric = is_numeric($command['valueTrue'] ?? 'ok') && is_numeric($command['valueFalse'] ?? 'ko');
        $expected['status::' . $command['id']] = array($command['name'], 'info', $numeric ? 'numeric' : 'string');
      }
    }
    // Supprime d'abord les commandes retirées (ou changées de type), pour libérer leur nom avant de créer les nouvelles
    foreach ($this->getCmd() as $cmd) {
      $logicalId = $cmd->getLogicalId();
      $isGroupCmd = strpos($logicalId, 'status::') === 0 || strpos($logicalId, 'action::') === 0;
      if ($isGroupCmd && !isset($expected[$logicalId])) {
        $cmd->remove();
      }
    }
    $order = 0;
    $this->createCmd('refresh', __('Rafraîchir', __FILE__), 'action', 'other', $order++, false);
    foreach ($expected as $logicalId => $def) {
      list($name, $type, $subType) = $def;
      $cmd = $this->createCmd($logicalId, $name, $type, $subType, $order, true);
      // Le nom suit celui défini par l'utilisateur (renommage dynamique)
      if ($cmd->getName() != $name || $cmd->getOrder() != $order || $cmd->getSubType() != $subType) {
        $cmd->setName($name);
        $cmd->setOrder($order);
        $cmd->setSubType($subType);
        $cmd->save();
      }
      $order++;
    }
    // Infos couleur des lumières résolues une fois ici (et non à chaque rafraîchissement)
    $colorCmds = array();
    foreach ($this->colorStateCmds() as $eqId => $info) {
      $colorCmds[$eqId] = $info->getId();
    }
    $this->setCache('colorCmds', $colorCmds);
    $this->setCache('state', null);
    $this->refreshState();
    $this->updateListener();
  }

  public function preRemove() {
    $listener = $this->getListener();
    if (is_object($listener)) {
      $listener->remove();
    }
  }

  private function createCmd($_logicalId, $_name, $_type, $_subType, $_order, $_visible) {
    $cmd = $this->getCmd(null, $_logicalId);
    if (is_object($cmd)) {
      return $cmd;
    }
    $cmd = new groupirCmd();
    $cmd->setLogicalId($_logicalId);
    $cmd->setEqLogic_id($this->getId());
    $cmd->setName($_name);
    $cmd->setType($_type);
    $cmd->setSubType($_subType);
    $cmd->setOrder($_order);
    $cmd->setIsVisible($_visible ? 1 : 0);
    $cmd->save();
    return $cmd;
  }

  private function getListener() {
    return listener::byClassAndFunction(__CLASS__, 'pull', array('eqLogic_id' => intval($this->getId())));
  }

  /** Commandes info des membres à surveiller. */
  private function getWatchedCmdIds() {
    $candidates = array();
    foreach ($this->getGroupCommands() as $command) {
      if (($command['type'] ?? 'info') == 'action') {
        continue;
      }
      foreach ($command['members'] ?? array() as $member) {
        $candidates[] = self::memberCmdId($member, 'info');
      }
    }
    // Infos couleur des lumières, affichées dans le widget
    foreach ($this->colorStateCmds() as $info) {
      $candidates[] = $info->getId();
    }
    $cmdIds = array();
    foreach ($candidates as $cmdId) {
      $cmd = ($cmdId === null) ? null : cmd::byId($cmdId);
      // Un groupe n'écoute jamais ses propres commandes
      if (is_object($cmd) && $cmd->getEqLogic_id() != $this->getId()) {
        $cmdIds[] = $cmdId;
      }
    }
    return array_unique($cmdIds);
  }

  /** (Re)crée l'écouteur sur les commandes surveillées. */
  public function updateListener() {
    $cmdIds = $this->getWatchedCmdIds();
    $listener = $this->getListener();
    if (count($cmdIds) == 0 || !$this->getIsEnable()) {
      if (is_object($listener)) {
        $listener->remove();
      }
      return;
    }
    if (!is_object($listener)) {
      $listener = new listener();
    }
    $listener->setClass(__CLASS__);
    $listener->setFunction('pull');
    $listener->setOption(array('eqLogic_id' => intval($this->getId())));
    $listener->emptyEvent();
    foreach ($cmdIds as $cmdId) {
      $listener->addEvent($cmdId);
    }
    $listener->save();
  }

  /*     * *********************Groupe************************* */

  /** Id de la commande info ou action d'une correspondance (null si absente). */
  private static function memberCmdId($_member, $_key) {
    $id = $_member[$_key . '_id'] ?? '';
    return is_numeric($id) ? $id : null;
  }

  /**
   * Règle d'une commande info : "all" (tous), "any" (au moins un), "atleast" (au moins ruleCount).
   * @return bool|null null s'il n'y a aucune ligne
   */
  public static function ruleMatched($_command, $_matches, $_total) {
    if ($_total == 0) {
      return null;
    }
    switch ($_command['rule'] ?? 'all') {
      case 'any':
        return $_matches >= 1;
      case 'atleast':
        return $_matches >= max(1, intval($_command['ruleCount'] ?? 1));
      default:
        return $_matches == $_total;
    }
  }

  /**
   * Nombre de correspondances vraies et nombre total pour une commande info du groupe.
   * @return array [vraies, total]
   */
  public function stateMatchCount($_state) {
    $total = 0;
    $matches = 0;
    foreach ($_state['members'] ?? array() as $member) {
      if (($member['type'] ?? '') == 'scenario') {
        $total++;
        if ($this->isScenarioInState($member['scenario_id'] ?? '', $member['scenarioState'] ?? 'active')) {
          $matches++;
        }
        continue;
      }
      $cmdId = self::memberCmdId($member, 'info');
      if ($cmdId === null) {
        continue; // ligne sans commande info choisie
      }
      $total++;
      $cmd = cmd::byId($cmdId);
      if (!is_object($cmd)) {
        log::add(__CLASS__, 'warning', '[' . $this->getHumanName() . '] ' . __('Commande info introuvable (supprimée ?)', __FILE__) . ' : ' . $cmdId . ' — ' . __('ouvrez le groupe pour corriger la ligne', __FILE__));
        continue;
      }
      $operator = ($member['operator'] ?? '') == '' ? '==' : $member['operator'];
      if (self::compare($cmd->execCmd(), $operator, self::resolveValue($member['value'] ?? ''))) {
        $matches++;
      }
    }
    return array($matches, $total);
  }

  /** État d'un scénario : "active", "inactive", "running" (en cours) ou "stopped" (arrêté). */
  private function isScenarioInState($_scenarioId, $_state) {
    $scenario = is_numeric($_scenarioId) ? scenario::byId($_scenarioId) : null;
    if (!is_object($scenario)) {
      log::add(__CLASS__, 'warning', '[' . $this->getHumanName() . '] ' . __('Scénario introuvable (supprimé ?)', __FILE__) . ' : ' . $_scenarioId);
      return false;
    }
    $running = in_array($scenario->getState(), array('in progress', 'starting'));
    switch ($_state) {
      case 'inactive':
        return $scenario->getIsActive() != 1;
      case 'running':
        return $running;
      case 'stopped':
        return !$running;
      default:
        return $scenario->getIsActive() == 1;
    }
  }

  /** Le groupe a-t-il des scénarios dans ses commandes info (leur état n'émet pas d'événement : rafraîchi par le cron) ? */
  private function hasScenarioInfo() {
    foreach ($this->getGroupCommands() as $command) {
      if (($command['type'] ?? 'info') == 'action') {
        continue;
      }
      foreach ($command['members'] ?? array() as $member) {
        if (($member['type'] ?? '') == 'scenario') {
          return true;
        }
      }
    }
    return false;
  }

  /** Chaque minute : recalcule les groupes dont une commande info dépend de l'état de scénarios. */
  public static function cron() {
    foreach (self::byType(__CLASS__, true) as $eqLogic) {
      if ($eqLogic->hasScenarioInfo()) {
        $eqLogic->refreshState();
      }
    }
  }

  /**
   * Met à jour chaque commande info du groupe : valeur "si tout correspond" ou valeur "sinon".
   * @param $_eventCmdId info d'un équipement qui vient de changer (null : tout recalculer)
   */
  public function refreshState($_eventCmdId = null) {
    $previous = $this->getCache('state', null);
    $state = $this->computeState($_eventCmdId, $previous);
    foreach ($this->getGroupCommands() as $command) {
      if (($command['type'] ?? 'info') == 'action' || !isset($state['counts'][$command['id']])) {
        continue;
      }
      list($matches, $total) = $state['counts'][$command['id']];
      $matched = self::ruleMatched($command, $matches, $total);
      $value = ($matched === null) ? '' : ($matched ? ($command['valueTrue'] ?? 'ok') : ($command['valueFalse'] ?? 'ko'));
      $this->checkAndUpdateCmd('status::' . $command['id'], $value);
    }
    // Widget mis à jour en direct (pastilles, compteurs, couleurs), seulement si quelque chose a changé
    $changed = ($state !== $previous);
    log::add(__CLASS__, 'debug', '[' . $this->getHumanName() . '] ' . __('Rafraîchissement', __FILE__) . ' (' . ($_eventCmdId === null ? __('complet', __FILE__) : __('info', __FILE__) . ' #' . $_eventCmdId) . ') : '
      . ($changed ? __('état modifié', __FILE__) : __('aucun changement', __FILE__)) . ', ' . __('couleurs', __FILE__) . ' ' . json_encode(array_column($state['colors'], 1)));
    if ($changed) {
      $this->setCache('state', $state);
      if (is_array($previous)) {
        event::add('groupir::update', $this->widgetPayload($state));
      }
    }
  }

  /*     * *********************Widget************************* */

  /**
   * Commande info "couleur" de chaque équipement du groupe : [eqLogic_id => cmd].
   * 1) l'info liée à une commande action couleur utilisée dans le groupe (valeur de retour de l'action) ;
   * 2) sinon, une info de type générique LIGHT_COLOR de l'équipement.
   */
  private function colorStateCmds() {
    $eqIds = array();
    $linked = array();
    foreach ($this->getGroupCommands() as $command) {
      foreach ($command['members'] ?? array() as $member) {
        $eqId = $member['eqLogic_id'] ?? '';
        if (!is_numeric($eqId) || $eqId == $this->getId()) {
          continue;
        }
        $eqIds[$eqId] = true;
        if (($command['type'] ?? 'info') != 'action' || isset($linked[$eqId])) {
          continue;
        }
        foreach (self::memberActions($member) as $action) {
          $actionCmd = cmd::byId($action['action_id']);
          if (!is_object($actionCmd) || $actionCmd->getSubType() != 'color' || !is_numeric($actionCmd->getValue())) {
            continue;
          }
          $info = cmd::byId($actionCmd->getValue());
          if (is_object($info) && $info->getType() == 'info') {
            $linked[$eqId] = $info;
            break;
          }
        }
      }
    }
    $result = array();
    foreach (array_keys($eqIds) as $eqId) {
      if (isset($linked[$eqId])) {
        $result[$eqId] = $linked[$eqId];
        continue;
      }
      $eqLogic = eqLogic::byId($eqId);
      if (!is_object($eqLogic)) {
        continue;
      }
      foreach ($eqLogic->getCmd('info') as $info) {
        if ($info->getGeneric_type() == 'LIGHT_COLOR') {
          $result[$eqId] = $info;
          break;
        }
      }
    }
    return $result;
  }

  /** Infos couleur des lumières : [eqLogic_id => cmd_id] (mémorisées à l'enregistrement, recalculées si le cache a été vidé). */
  private function colorCmdIds() {
    $ids = $this->getCache('colorCmds', null);
    if (!is_array($ids)) {
      $ids = array();
      foreach ($this->colorStateCmds() as $eqId => $info) {
        $ids[$eqId] = $info->getId();
      }
      $this->setCache('colorCmds', $ids);
    }
    return $ids;
  }

  /** Couleur actuelle de chaque lumière du groupe : [[nom, #rrggbb], ...] */
  private function currentColors() {
    $colors = array();
    foreach ($this->colorCmdIds() as $cmdId) {
      $info = cmd::byId($cmdId);
      if (!is_object($info)) {
        continue;
      }
      $value = strtolower(trim((string) $info->execCmd()));
      if (preg_match('/^[0-9a-f]{6}$/', $value)) {
        $value = '#' . $value;
      }
      if (!preg_match('/^#[0-9a-f]{6}$/', $value)) {
        continue;
      }
      $eqLogic = $info->getEqLogic();
      $colors[] = array(is_object($eqLogic) ? $eqLogic->getName() : '', $value);
    }
    return $colors;
  }

  /** La commande info du groupe dépend-elle de l'info $_cmdId d'un équipement ? */
  private static function commandUsesCmd($_command, $_cmdId) {
    foreach ($_command['members'] ?? array() as $member) {
      if ((string) self::memberCmdId($member, 'info') === (string) $_cmdId) {
        return true;
      }
    }
    return false;
  }

  /**
   * État du groupe gardé en cache : ['counts' => [id => [vraies, total]], 'colors' => [[nom, #rrggbb]]].
   * Calculé entièrement s'il est absent.
   */
  private function getState() {
    $state = $this->getCache('state', null);
    if (!is_array($state) || !isset($state['counts'], $state['colors'])) {
      $state = $this->computeState(null, null);
      $this->setCache('state', $state);
    }
    return $state;
  }

  /**
   * Calcule l'état du groupe. Avec $_eventCmdId, ne recalcule que les commandes qui utilisent cette info
   * (et les couleurs seulement si c'est une info couleur) ; le reste vient de $_previous.
   */
  private function computeState($_eventCmdId, $_previous) {
    $previous = is_array($_previous) ? $_previous : array();
    $partial = ($_eventCmdId !== null && isset($previous['counts'], $previous['colors']));
    $counts = array();
    foreach ($this->getGroupCommands() as $command) {
      if (($command['type'] ?? 'info') == 'action') {
        continue;
      }
      $id = $command['id'];
      if ($partial && isset($previous['counts'][$id]) && !self::commandUsesCmd($command, $_eventCmdId)) {
        $counts[$id] = $previous['counts'][$id];
      } else {
        $counts[$id] = $this->stateMatchCount($command);
      }
    }
    if ($partial && !in_array((string) $_eventCmdId, array_map('strval', $this->colorCmdIds()))) {
      $colors = $previous['colors'];
    } else {
      $colors = $this->currentColors();
    }
    return array('counts' => $counts, 'colors' => $colors);
  }

  /** Données du widget pour une mise à jour en direct (sans le régénérer). */
  private function widgetPayload($_state) {
    $infos = array();
    foreach ($this->getGroupCommands() as $command) {
      if (($command['type'] ?? 'info') == 'action' || !isset($_state['counts'][$command['id']])) {
        continue;
      }
      $cmd = $this->getCmd('info', 'status::' . $command['id']);
      if (!is_object($cmd)) {
        continue;
      }
      list($matches, $total) = $_state['counts'][$command['id']];
      $matched = self::ruleMatched($command, $matches, $total);
      $infos[] = array(
        'cmd_id' => $cmd->getId(),
        'status' => ($matched === null) ? 'is-none' : ($matched ? 'is-ok' : 'is-ko'),
        'count' => ($total == 0) ? '' : $matches . '/' . $total,
        'title' => ($total == 0) ? __('Aucun équipement', __FILE__) : $matches . '/' . $total . ' ' . __('équipements conformes', __FILE__),
        'value' => (string) $cmd->execCmd(),
      );
    }
    $colors = array();
    foreach ($_state['colors'] as $color) {
      $colors[] = array('name' => $color[0], 'color' => $color[1]);
    }
    return array('eqLogic_id' => $this->getId(), 'infos' => $infos, 'colors' => $colors);
  }

  public function toHtml($_version = 'dashboard') {
    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    $refresh = $this->getCmd('action', 'refresh');
    $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
    $infos = '';
    $actions = '';
    $state = $this->getState();
    foreach ($this->getGroupCommands() as $command) {
      $isAction = ($command['type'] ?? 'info') == 'action';
      $cmd = $this->getCmd(null, ($isAction ? 'action::' : 'status::') . $command['id']);
      if (!is_object($cmd) || $cmd->getIsVisible() != 1) {
        continue;
      }
      $name = htmlspecialchars($cmd->getName());
      if ($isAction) {
        $actions .= '<a class="btn btn-sm gw-action" data-groupir-cmd="' . $cmd->getId() . '" title="' . $name . '">' . $name . '</a>';
        continue;
      }
      list($matches, $total) = $state['counts'][$command['id']] ?? $this->stateMatchCount($command);
      $value = (string) $cmd->execCmd();
      $matched = self::ruleMatched($command, $matches, $total);
      $status = ($matched === null) ? 'is-none' : ($matched ? 'is-ok' : 'is-ko');
      $title = $total == 0 ? __('Aucun équipement', __FILE__) : $matches . '/' . $total . ' ' . __('équipements conformes', __FILE__);
      $infos .= '<div class="gw-info ' . $status . '" data-cmd_id="' . $cmd->getId() . '" title="' . htmlspecialchars($title) . '">';
      $infos .= '<span class="gw-dot"></span>';
      $infos .= '<span class="gw-info-name">' . $name . '</span>';
      $infos .= '<span class="gw-info-count">' . ($total == 0 ? '' : $matches . '/' . $total) . '</span>';
      $infos .= '<span class="gw-pill">' . ($value === '' ? '-' : htmlspecialchars($value)) . '</span>';
      $infos .= '</div>';
    }
    $replace['#infos#'] = ($infos == '') ? '' : '<div class="gw-infos">' . $infos . '</div>';
    // Couleur actuelle des lumières
    $colors = '';
    foreach ($state['colors'] as $color) {
      $colors .= '<span class="gw-swatch" style="background:' . $color[1] . ';" title="' . htmlspecialchars($color[0] . ' : ' . $color[1]) . '"></span>';
    }
    $replace['#colors#'] = ($colors == '') ? '' : '<div class="gw-colors"><i class="fas fa-palette" title="' . __('Couleur actuelle', __FILE__) . '"></i>' . $colors . '</div>';
    $replace['#actions#'] = ($actions == '') ? '' : '<div class="gw-actions">' . $actions . '</div>';
    $replace['#empty#'] = ($infos == '' && $actions == '' && $colors == '') ? '<div class="gw-empty">' . __('Aucune commande', __FILE__) . '</div>' : '';
    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'groupir', __CLASS__)));
  }

  /**
   * Actions d'un équipement d'une commande action, dans l'ordre : [{action_id, actionValue}].
   * Reprend l'ancien format (une seule action : action_id / actionValue sur l'équipement).
   */
  public static function memberActions($_member) {
    $list = (isset($_member['actions']) && is_array($_member['actions'])) ? $_member['actions'] : array($_member);
    $actions = array();
    foreach ($list as $action) {
      if (is_array($action) && is_numeric($action['action_id'] ?? '')) {
        $actions[] = array('action_id' => $action['action_id'], 'actionValue' => (string) ($action['actionValue'] ?? ''));
      }
    }
    return $actions;
  }

  /** Exécute une commande action du groupe : l'action de chaque équipement, dans l'ordre. */
  public function runGroupAction($_commandId) {
    foreach ($this->getGroupCommands() as $command) {
      if ($command['id'] != $_commandId || ($command['type'] ?? 'info') != 'action') {
        continue;
      }
      log::add(__CLASS__, 'info', '[' . $this->getHumanName() . '] ' . __('Exécution de', __FILE__) . ' ' . $command['name']);
      // Pause optionnelle entre deux actions (ex. certaines ampoules ignorent une couleur envoyée juste après l'allumage)
      $pause = max(0, min(30, floatval($command['pause'] ?? 0)));
      $first = true;
      $wait = function () use (&$first, $pause) {
        if (!$first && $pause > 0) {
          usleep(intval($pause * 1000000));
        }
        $first = false;
      };
      foreach ($command['members'] ?? array() as $member) {
        if (($member['type'] ?? '') == 'scenario') {
          $wait();
          $this->runScenario($member['scenario_id'] ?? '', $member['scenarioAction'] ?? 'start');
          continue;
        }
        foreach (self::memberActions($member) as $action) {
          $wait();
          $this->execActionWithValue($action['action_id'], $action['actionValue']);
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Scénarios liés au groupe :
   *  - using   : scénarios qui utilisent une commande du groupe (déclencheur, condition, action) ;
   *  - piloted : scénarios lancés / arrêtés / activés / désactivés par une commande action du groupe.
   */
  public function getLinkedScenarios() {
    $format = function ($_scenario, $_extra = array()) {
      return array_merge(array(
        'id' => $_scenario->getId(),
        'name' => $_scenario->getHumanName(),
        'isActive' => $_scenario->getIsActive(),
      ), $_extra);
    };
    $using = array();
    foreach ($this->getCmd() as $cmd) {
      if (!method_exists($cmd, 'getUsedBy')) {
        break;
      }
      $usedBy = $cmd->getUsedBy();
      foreach ($usedBy['scenario'] ?? array() as $scenario) {
        if (is_array($scenario)) {
          $scenario = scenario::byId($scenario['id'] ?? '');
        }
        if (is_object($scenario) && !isset($using[$scenario->getId()])) {
          $using[$scenario->getId()] = $format($scenario);
        }
      }
    }
    $labels = array(
      'start' => __('Lancer', __FILE__),
      'stop' => __('Arrêter', __FILE__),
      'activate' => __('Activer', __FILE__),
      'deactivate' => __('Désactiver', __FILE__),
    );
    $piloted = array();
    foreach ($this->getGroupCommands() as $command) {
      foreach ($command['members'] ?? array() as $member) {
        if (($member['type'] ?? '') != 'scenario') {
          continue;
        }
        $scenario = scenario::byId($member['scenario_id'] ?? '');
        if (!is_object($scenario)) {
          continue;
        }
        $action = $labels[$member['scenarioAction'] ?? 'start'] ?? $labels['start'];
        $piloted[] = $format($scenario, array('by' => $command['name'] . ' : ' . $action));
      }
    }
    return array('using' => array_values($using), 'piloted' => $piloted);
  }

  /** Lance, arrête, active ou désactive un scénario. */
  private function runScenario($_scenarioId, $_action) {
    $scenario = is_numeric($_scenarioId) ? scenario::byId($_scenarioId) : null;
    if (!is_object($scenario)) {
      log::add(__CLASS__, 'warning', '[' . $this->getHumanName() . '] ' . __('Scénario introuvable', __FILE__) . ' : ' . $_scenarioId);
      return false;
    }
    try {
      log::add(__CLASS__, 'debug', '[' . $this->getHumanName() . '] ' . __('Scénario', __FILE__) . ' ' . $scenario->getHumanName() . ' : ' . $_action);
      switch ($_action) {
        case 'stop':
          $scenario->stop();
          break;
        case 'activate':
          $scenario->setIsActive(1);
          $scenario->save();
          break;
        case 'deactivate':
          $scenario->setIsActive(0);
          $scenario->save();
          break;
        default:
          $scenario->launch('groupir', __('Lancé par le groupe', __FILE__) . ' ' . $this->getHumanName());
      }
      return true;
    } catch (Throwable $e) {
      log::add(__CLASS__, 'error', '[' . $this->getHumanName() . '] ' . __('Erreur sur le scénario', __FILE__) . ' ' . $scenario->getHumanName() . ' : ' . $e->getMessage());
    }
    return false;
  }

  /** Exécute une commande action en plaçant la valeur dans l'option attendue par son sous-type. */
  private function execActionWithValue($_cmdId, $_value) {
    if (!is_numeric($_cmdId)) {
      return false;
    }
    $cmd = cmd::byId($_cmdId);
    if (!is_object($cmd) || $cmd->getType() != 'action' || $cmd->getEqLogic_id() == $this->getId()) {
      log::add(__CLASS__, 'warning', '[' . $this->getHumanName() . '] ' . __('Commande action invalide', __FILE__) . ' : ' . $_cmdId);
      return false;
    }
    if (is_string($_value) && strpos($_value, '#') !== false) {
      $_value = cmd::cmdToValue($_value);
    }
    $options = array();
    if ($_value !== '' && $_value !== null) {
      switch ($cmd->getSubType()) {
        case 'slider':
          // Ramène la valeur dans la plage de la commande (ex. température de couleur 153 à 500)
          if (is_numeric($_value)) {
            $min = $cmd->getConfiguration('minValue', '');
            $max = $cmd->getConfiguration('maxValue', '');
            if (is_numeric($min) && $_value < $min) {
              $_value = $min;
            }
            if (is_numeric($max) && $_value > $max) {
              $_value = $max;
            }
          }
          $options['slider'] = $_value;
          break;
        case 'color':
          $options['color'] = $_value;
          break;
        case 'select':
          $options['select'] = $_value;
          break;
        case 'message':
          $options['message'] = $_value;
          break;
      }
    }
    try {
      log::add(__CLASS__, 'debug', '[' . $this->getHumanName() . '] ' . $cmd->getHumanName() . ' ' . json_encode($options));
      $cmd->execCmd($options);
      return true;
    } catch (Throwable $e) {
      log::add(__CLASS__, 'error', '[' . $this->getHumanName() . '] ' . __('Erreur sur', __FILE__) . ' ' . $cmd->getHumanName() . ' : ' . $e->getMessage());
    }
    return false;
  }

  private function getArrayConfiguration($_key) {
    $value = $this->getConfiguration($_key, array());
    if (!is_array($value)) {
      return array();
    }
    return array_values(array_filter($value, function ($item) {
      return is_array($item);
    }));
  }
}

class groupirCmd extends cmd {

  /* Les commandes sont gérées par le plugin, on ne les supprime jamais depuis l'interface */
  public function dontRemoveCmd() {
    return true;
  }

  public function execute($_options = array()) {
    /** @var groupir $eqLogic */
    $eqLogic = $this->getEqLogic();
    if ($this->getLogicalId() == 'refresh') {
      $eqLogic->refreshState();
    } elseif (strpos($this->getLogicalId(), 'action::') === 0) {
      $eqLogic->runGroupAction(substr($this->getLogicalId(), 8));
    }
  }
}

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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    // Liste de tous les équipements (pour les listes déroulantes), triée par objet puis par nom
    if (init('action') == 'getEqLogics') {
        $list = array();
        foreach (eqLogic::all() as $eqLogic) {
            $object = $eqLogic->getObject();
            $list[] = array(
                'id' => $eqLogic->getId(),
                'name' => $eqLogic->getName(),
                'object_id' => is_object($object) ? $object->getId() : '',
                'object' => is_object($object) ? $object->getName() : __('Aucun', __FILE__),
                'isEnable' => $eqLogic->getIsEnable(),
            );
        }
        usort($list, function ($a, $b) {
            return strcasecmp($a['object'] . ' ' . $a['name'], $b['object'] . ' ' . $b['name']);
        });
        ajax::success($list);
    }

    // Scénarios liés au groupe : ceux qui utilisent ses commandes et ceux qu'il pilote
    if (init('action') == 'getLinkedScenarios') {
        $eqLogic = groupir::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Groupe introuvable', __FILE__) . ' : ' . init('id'));
        }
        ajax::success($eqLogic->getLinkedScenarios());
    }

    // Valeur actuelle des commandes info du groupe (affichée dans l'en-tête de chaque commande)
    if (init('action') == 'getInfoValues') {
        $eqLogic = groupir::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Groupe introuvable', __FILE__) . ' : ' . init('id'));
        }
        ajax::success($eqLogic->getInfoValues());
    }

    // Valeur actuelle de commandes info (lignes de l'éditeur, rafraîchies en direct)
    if (init('action') == 'getCmdValues') {
        $ids = json_decode(init('ids', '[]'), true);
        $values = array();
        foreach (is_array($ids) ? array_slice($ids, 0, 500) : array() as $id) {
            if (!is_numeric($id)) {
                continue;
            }
            $cmd = cmd::byId($id);
            if (is_object($cmd) && $cmd->getType() == 'info') {
                $values[] = array('id' => $cmd->getId(), 'value' => $cmd->execCmd());
            }
        }
        ajax::success($values);
    }

    // Liste des scénarios (pour les commandes action du groupe)
    if (init('action') == 'getScenarios') {
        $list = array();
        foreach (scenario::all() as $scenario) {
            $group = trim((string) $scenario->getGroup());
            $list[] = array(
                'id' => $scenario->getId(),
                'name' => $scenario->getName(),
                'group' => ($group == '') ? __('Aucun', __FILE__) : $group,
                'isActive' => $scenario->getIsActive(),
            );
        }
        usort($list, function ($a, $b) {
            return strcasecmp($a['group'] . ' ' . $a['name'], $b['group'] . ' ' . $b['name']);
        });
        ajax::success($list);
    }

    // Retourne le nom, les commandes "action" (cmds) et "info" (infos) d'un équipement
    if (init('action') == 'getActionCmds') {
        $eqLogic = eqLogic::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Équipement introuvable', __FILE__) . ' : ' . init('id'));
        }
        $cmds = array();
        foreach ($eqLogic->getCmd('action') as $cmd) {
            $cmds[] = array(
                'id' => $cmd->getId(),
                'name' => $cmd->getName(),
                'subType' => $cmd->getSubType(),
                'listValue' => $cmd->getConfiguration('listValue', ''),
                'minValue' => $cmd->getConfiguration('minValue', ''),
                'maxValue' => $cmd->getConfiguration('maxValue', ''),
            );
        }
        $infos = array();
        foreach ($eqLogic->getCmd('info') as $cmd) {
            $infos[] = array(
                'id' => $cmd->getId(),
                'name' => $cmd->getName(),
                'subType' => $cmd->getSubType(),
                'value' => $cmd->execCmd(),
            );
        }
        ajax::success(array(
            'infos' => $infos,
            'id' => $eqLogic->getId(),
            'humanName' => $eqLogic->getHumanName(),
            'isEnable' => $eqLogic->getIsEnable(),
            'cmds' => $cmds,
        ));
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

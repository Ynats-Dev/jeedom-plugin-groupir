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

var GR_AJAX_URL = 'plugins/groupir/core/ajax/groupir.ajax.php'

var GR_OPERATORS = [
  { value: '==', label: '{{égal à}}' },
  { value: '!=', label: '{{différent de}}' },
  { value: '>', label: '{{supérieur à}}' },
  { value: '>=', label: '{{supérieur ou égal à}}' },
  { value: '<', label: '{{inférieur à}}' },
  { value: '<=', label: '{{inférieur ou égal à}}' },
  { value: 'contains', label: '{{contient}}' },
  { value: 'not_contains', label: '{{ne contient pas}}' }
]

function grOperatorOptions(_withAny) {
  var html = _withAny ? '<option value="">{{quelle que soit la valeur}}</option>' : ''
  for (var i in GR_OPERATORS) {
    html += '<option value="' + GR_OPERATORS[i].value + '">' + GR_OPERATORS[i].label + '</option>'
  }
  return html
}

function grRefreshEmpty() {
  $('.gr-empty').each(function () {
    $(this).toggle($('#' + $(this).data('for')).children().length == 0)
  })
  $('#span_grCount').text($('#div_grStates').children('.gr-state').length)
}

function grAlert(_message) {
  $('#div_alert').showAlert({ message: _message, level: 'danger' })
}

/* ============================================================
 * Commandes de l'équipement : non affichées (gérées par le plugin,
 * accessibles via "Configuration avancée"). Le core appelle addCmdToTable
 * pour chaque commande : rien à afficher.
 * ============================================================ */
/* Identifiants des commandes Jeedom du groupe (logicalId -> id), pour ouvrir leur configuration */
var grCmdIdsByLogical = {}

function addCmdToTable(_cmd) {
  if (_cmd && init(_cmd.logicalId) != '') {
    grCmdIdsByLogical[_cmd.logicalId] = _cmd.id
    grApplyCmdIds()
  }
}

function grApplyCmdIds() {
  $('#div_grStates').children('.gr-state').each(function () {
    var id = String(init($(this).find('.grStateAttr[data-l1key=id]').val()))
    var logicalId = ($(this).attr('data-type') == 'action' ? 'action::' : 'status::') + id
    var cmdId = (id != '') ? grCmdIdsByLogical[logicalId] : undefined
    var button = $(this).find('.bt_grConfigureCmd')
    button.attr('data-cmd_id', init(cmdId)).toggleClass('gr-disabled', !cmdId)
  })
}

/* Fenêtre standard de configuration d'une commande Jeedom (historique, type générique, alertes, affichage...) */
function grOpenCmdConfigure(_cmdId) {
  var url = 'index.php?v=d&modal=cmd.configure&cmd_id=' + encodeURIComponent(_cmdId)
  if (typeof jeeDialog !== 'undefined' && typeof jeeDialog.dialog === 'function') {
    jeeDialog.dialog({ id: 'jee_modal', title: '{{Configuration de la commande}}', contentUrl: url })
  } else {
    $('#md_modal').dialog({ title: '{{Configuration de la commande}}' }).load(url).dialog('open')
  }
}

/* ============================================================
 * Commandes du groupe
 * ============================================================ */
/* Cache des équipements : id -> {humanName, cmds (actions), infos} */
var grGroupEqCache = {}

function grFetchEqLogic(_id) {
  var deferred = $.Deferred()
  if (grGroupEqCache[_id]) {
    return deferred.resolve(grGroupEqCache[_id]).promise()
  }
  $.ajax({
    type: 'POST',
    url: GR_AJAX_URL,
    data: { action: 'getActionCmds', id: _id },
    dataType: 'json',
    global: false,
    error: function () {
      deferred.resolve(null)
    },
    success: function (data) {
      if (data.state != 'ok') {
        deferred.resolve(null)
        return
      }
      grGroupEqCache[_id] = data.result
      deferred.resolve(data.result)
    }
  })
  return deferred.promise()
}

/*
 * Carte d'une commande du groupe (sous-groupe d'équipements), de type :
 *  - "info"   : une commande info + valeur attendue par équipement, réponse "si tout correspond" / "sinon"
 *  - "action" : une commande action (+ valeur) par équipement, exécutées par la commande action du groupe
 */
function grAddState(_state) {
  _state = _state || {}
  var type = (init(_state.type) == 'action') ? 'action' : 'info'
  var isInfo = (type == 'info')
  var div = '<div class="gr-state gr-state-' + type + '" data-type="' + type + '">'
  div += '<input class="grStateAttr" data-l1key="id" style="display:none;">'
  div += '<div class="gr-state-header">'
  div += '<span class="gr-drag" title="{{Glisser pour réordonner}}"><i class="fas fa-grip-vertical"></i></span>'
  div += '<a class="gr-toggle bt_grToggleState" title="{{Plier / déplier}}"><i class="fas fa-chevron-down"></i></a>'
  div += isInfo ? '<span class="groupir-badge is-info"><i class="fas fa-eye"></i> {{Info}}</span>' : '<span class="groupir-badge is-action"><i class="fas fa-hand-pointer"></i> {{Action}}</span>'
  div += '<input class="grStateAttr form-control input-sm" data-l1key="name" placeholder="' + (isInfo ? '{{Nom, ex. on, off}}' : '{{Nom, ex. Allumer, Éteindre}}') + '">'
  div += '<span class="groupir-meta gr-members-count"></span>'
  div += '<a class="btn btn-default btn-xs bt_grAddMemberEq"><i class="fas fa-plus-circle"></i> {{Équipement}}</a>'
  div += '<a class="btn btn-default btn-xs bt_grAddMemberScenario"><i class="fas fa-cogs"></i> {{Scénario}}</a>'
  div += '<a class="btn btn-default btn-xs bt_grConfigureCmd" title="{{Configuration de la commande : historique, type générique, alertes, affichage}}"><i class="fas fa-cog"></i></a>'
  div += '<a class="btn btn-default btn-xs bt_grCloneState" title="{{Cloner cette commande et ses équipements}}"><i class="fas fa-clone"></i></a>'
  div += '<a class="btn btn-danger btn-xs bt_grRemoveState" title="{{Supprimer cette commande}}"><i class="fas fa-trash-alt"></i></a>'
  div += '</div>'
  div += '<div class="gr-state-body">'
  div += '<div class="gr-members-head">'
  div += '<span>{{Objet}}</span><span>{{Équipement}}</span>'
  div += isInfo ? '<span>{{Commande info}}</span><span>{{Valeur attendue}}</span><span></span>' : '<span>{{Commandes action (dans l\'ordre)}}</span><span>{{Valeur}}</span><span></span><span></span>'
  div += '</div>'
  div += '<div class="gr-members"></div>'
  div += '<div class="gr-members-empty"><i class="fas fa-plug"></i> {{Aucun équipement : cliquez sur "Équipement" pour en ajouter un.}}</div>'
  if (isInfo) {
    div += '<div class="gr-state-values">'
    div += '<span class="gr-state-values-title"><i class="fas fa-reply"></i> {{Valeurs renvoyées par la commande}}</span>'
    div += '<select class="grStateAttr form-control input-sm gr-rule" data-l1key="rule">'
    div += '<option value="all">{{si tous correspondent}}</option><option value="any">{{si au moins un correspond}}</option><option value="atleast">{{si au moins N correspondent}}</option>'
    div += '</select>'
    div += '<input type="number" min="1" step="1" class="grStateAttr form-control input-sm gr-rule-count" data-l1key="ruleCount" placeholder="N" title="{{Nombre minimum de lignes qui correspondent}}">'
    div += '<label>{{alors}}</label><input class="grStateAttr form-control input-sm" data-l1key="valueTrue" placeholder="ok">'
    div += '<label>{{sinon}}</label><input class="grStateAttr form-control input-sm" data-l1key="valueFalse" placeholder="ko">'
    div += '</div>'
  } else {
    div += '<div class="gr-state-values">'
    div += '<span class="gr-state-values-title"><i class="fas fa-hourglass-half"></i> {{Pause entre deux actions}}</span>'
    div += '<input type="number" min="0" max="30" step="0.5" class="grStateAttr form-control input-sm gr-pause" data-l1key="pause" placeholder="0"><label>{{secondes}}</label>'
    div += '<span class="groupir-meta gr-pause-help"><i class="fas fa-info-circle"></i> {{Les lignes s\'exécutent dans l\'ordre (glissez-les pour le changer). Utile si une ampoule ignore une couleur envoyée juste après l\'allumage.}}</span>'
    div += '</div>'
  }
  div += '</div>'
  div += '</div>'
  var state = $(div)
  $('#div_grStates').append(state)
  state.setValues(_state, '.grStateAttr')
  if (isInfo) {
    state.find('.gr-rule').val(init(_state.rule, 'all'))
    grRefreshRule(state)
  }
  var members = _state.members || []
  for (var i in members) {
    if (init(members[i].type) == 'scenario') {
      grAddScenarioMember(state, members[i])
    } else if (init(members[i].eqLogic_id) != '') {
      grAddMember(state, String(members[i].eqLogic_id), members[i])
    }
  }
  grRefreshSiblingEqs(null, state)
  grRefreshValueColumn(state)
  grRefreshMembersEmpty(state)
  grMakeSortable(state)
  grApplyCmdIds()
  // Commandes enregistrées pliées par défaut (sauf celles dépliées par l'utilisateur) ; une nouvelle commande est dépliée
  var stateId = String(init(_state.id))
  grSetCollapsed(state, stateId != '' && grExpandedIds().indexOf(stateId) < 0)
  grRefreshEmpty()
  return state
}

/* ---------- Plier / déplier les commandes (pliées par défaut, dépliées mémorisées dans le navigateur) ---------- */

var GR_EXPANDED_KEY = 'groupir.expanded'

function grExpandedIds() {
  try {
    var ids = JSON.parse(localStorage.getItem(GR_EXPANDED_KEY) || '[]')
    return $.isArray(ids) ? ids : []
  } catch (e) {
    return []
  }
}

function grSetCollapsed(_state, _collapsed) {
  _state.toggleClass('gr-collapsed', !!_collapsed)
  var id = String(init(_state.find('.grStateAttr[data-l1key=id]').val()))
  if (id == '') {
    return
  }
  try {
    var ids = $.grep(grExpandedIds(), function (i) { return i != id })
    if (!_collapsed) {
      ids.push(id)
    }
    localStorage.setItem(GR_EXPANDED_KEY, JSON.stringify(ids.slice(-500)))
  } catch (e) {
  }
}

/* Liste de tous les équipements (chargée une fois) pour les listes déroulantes */
var grEqLogicList = null

function grLoadEqLogicList() {
  var deferred = $.Deferred()
  if (grEqLogicList !== null) {
    return deferred.resolve(grEqLogicList).promise()
  }
  $.ajax({
    type: 'POST',
    url: GR_AJAX_URL,
    data: { action: 'getEqLogics' },
    dataType: 'json',
    global: false,
    error: function () {
      deferred.resolve([])
    },
    success: function (data) {
      grEqLogicList = (data.state == 'ok') ? data.result : []
      deferred.resolve(grEqLogicList)
    }
  })
  return deferred.promise()
}

/* ---------- Sélection en cascade Objet > Équipement > Commande info, avec filtres ---------- */

function grMatch(_text, _filter) {
  return init(_filter) == '' || String(_text).toLowerCase().indexOf(String(_filter).toLowerCase()) >= 0
}

/* Objets possédant au moins un équipement ("none" = aucun objet) */
function grObjectItems() {
  var selfId = String($('.eqLogicAttr[data-l1key=id]').value())
  var items = []
  var seen = {}
  for (var i in grEqLogicList || []) {
    var eq = grEqLogicList[i]
    var id = String(init(eq.object_id)) == '' ? 'none' : String(eq.object_id)
    if (String(eq.id) == selfId || seen[id]) {
      continue
    }
    seen[id] = true
    items.push({ id: id, name: eq.object })
  }
  return items
}

/* Équipements déjà choisis dans les autres lignes de la même commande */
function grUsedEqIds(_row) {
  return _row.siblings('.gr-member').map(function () {
    return String(init($(this).attr('data-eqLogic_id')))
  }).get().filter(function (id) { return id != '' })
}

/* Équipements d'un objet (tous si aucun objet choisi), sans le groupe lui-même ni ceux exclus */
function grEqItems(_objectId, _exclude) {
  var selfId = String($('.eqLogicAttr[data-l1key=id]').value())
  var exclude = _exclude || []
  var items = []
  for (var i in grEqLogicList || []) {
    var eq = grEqLogicList[i]
    var objId = String(init(eq.object_id)) == '' ? 'none' : String(eq.object_id)
    if (String(eq.id) == selfId || exclude.indexOf(String(eq.id)) >= 0 || (_objectId != '' && objId != _objectId)) {
      continue
    }
    var label = (_objectId == '' ? '[' + eq.object + '] ' : '') + eq.name + (eq.isEnable == 1 ? '' : ' ({{inactif}})')
    items.push({ id: String(eq.id), name: label, objectId: objId })
  }
  return items
}

function grRowType(_row) {
  return _row.closest('.gr-state').attr('data-type') == 'action' ? 'action' : 'info'
}

/* Commandes info ou action d'un équipement */
function grCmdItems(_eqLogicId, _type) {
  var data = grGroupEqCache[_eqLogicId]
  var list = data ? (_type == 'action' ? data.cmds : data.infos) : []
  return $.map(list || [], function (cmd) {
    return $.extend({}, cmd, { id: String(cmd.id) })
  })
}

/*
 * Remplit une liste avec les éléments correspondant au filtre.
 * Garde la sélection si elle correspond encore ; si le filtre ne laisse qu'un résultat, il est sélectionné.
 */
function grFillSelect(_select, _items, _placeholder, _filter, _current) {
  var current = String(init(_current, init(_select.val())))
  _select.empty().append($('<option value="">').text(_placeholder))
  var matches = []
  for (var i in _items) {
    if (grMatch(_items[i].name, _filter)) {
      matches.push(_items[i])
      $('<option>').val(_items[i].id).text(_items[i].name).data('item', _items[i]).appendTo(_select)
    }
  }
  var keep = matches.some(function (m) { return String(m.id) == current })
  if (keep) {
    _select.val(current)
  } else if (init(_filter) != '' && matches.length == 1) {
    _select.val(matches[0].id)
  } else {
    _select.val('')
  }
  return String(_select.val() || '')
}

function grRowFilter(_row, _for) {
  return _row.find('.gr-filter[data-for=' + _for + ']').val()
}

function grRowRenderObjects(_row, _current) {
  grFillSelect(_row.find('.gr-obj'), grObjectItems(), '{{Tous les objets}}', grRowFilter(_row, 'obj'), _current)
}

function grRowRenderEqs(_row, _current) {
  var before = String(init(_row.attr('data-eqLogic_id')))
  var value = grFillSelect(_row.find('.grMemberAttr[data-l1key=eqLogic_id]'), grEqItems(String(_row.find('.gr-obj').val() || ''), grUsedEqIds(_row)), '{{Équipement...}}', grRowFilter(_row, 'eq'), _current)
  if (value != before) {
    grRowEqChanged(_row)
  }
}

/* Info : liste de la commande info. Action : liste de chaque action de l'équipement (remise à zéro) */
function grRowRenderCmds(_row, _current) {
  if (grRowType(_row) == 'action') {
    // Chaque action garde son nom : on retrouve la commande du même nom sur l'équipement choisi
    var eqId = String(init(_row.attr('data-eqLogic_id')))
    _row.find('.gr-step').each(function () {
      var step = $(this)
      var name = grSelectedName(step.find('.grStepAttr[data-l1key=action_id]')) || init(step.data('grName'))
      var value = init(step.find('.grStepAttr[data-l1key=actionValue]').val(), init(step.data('grValue')))
      step.data('grName', name).data('grValue', value)
      grStepRenderCmds(step, grCmdIdByName(eqId, 'action', name), value)
    })
    return
  }
  grFillSelect(_row.find('.grMemberAttr[data-l1key=info_id]'), grCmdItems(_row.attr('data-eqLogic_id'), 'info'), '{{Commande info...}}', grRowFilter(_row, 'cmd'), _current)
  grUpdateMemberHints(_row)
}

function grStepRenderCmds(_step, _current, _actionValue) {
  var row = _step.closest('.gr-member')
  var name = init(_step.data('grName'))
  var placeholder = (name != '') ? name + ' ({{à choisir}})' : '{{Commande action...}}'
  grFillSelect(_step.find('.grStepAttr[data-l1key=action_id]'), grCmdItems(row.attr('data-eqLogic_id'), 'action'), placeholder, _step.find('.gr-filter').val(), _current)
  var fieldValue = _step.find('.grStepAttr[data-l1key=actionValue]').val()
  grUpdateStepValue(_step, init(_actionValue, init(fieldValue, init(_step.data('grValue')))))
}

/* Actions d'un équipement (reprend l'ancien format à une seule action) */
function grMemberActions(_member) {
  var list = $.isArray(_member.actions) ? _member.actions : [_member]
  return $.grep(list, function (a) { return a && init(a.action_id) != '' })
}

/* Ajoute une action (commande + valeur) à un équipement d'une commande action */
function grAddStep(_row, _action) {
  _action = _action || {}
  var step = $('<div class="gr-step">')
  step.data('grName', init(_action.name)).data('grValue', init(_action.actionValue))
  step.append(grPick($('<select class="grStepAttr form-control input-sm" data-l1key="action_id">'), 'cmd'))
  step.append('<div class="gr-pick-test"></div>')
  step.append('<div class="gr-step-remove"><span class="gr-step-drag" title="{{Glisser pour réordonner}}"><i class="fas fa-grip-vertical"></i></span><a class="btn btn-default btn-xs bt_grRemoveStep" title="{{Retirer cette action}}"><i class="fas fa-times"></i></a></div>')
  _row.find('.gr-step-add').before(step)
  grStepRenderCmds(step, init(_action.action_id), init(_action.actionValue))
  if (init(_action.action_id) != '' && String(step.find('.grStepAttr[data-l1key=action_id]').val() || '') == '') {
    step.addClass('gr-step-broken')
  }
  grRefreshSteps(_row)
  return step
}

function grRefreshSteps(_row) {
  _row.toggleClass('gr-single-step', _row.find('.gr-step').length <= 1)
}

/* Met à jour la liste "Équipement" des autres lignes de la commande (sans changer leur choix) */
function grRefreshSiblingEqs(_row, _state) {
  var rows = _row ? _row.siblings('.gr-member') : _state.find('.gr-member')
  rows.each(function () {
    var other = $(this)
    var current = String(init(other.attr('data-eqLogic_id')))
    grFillSelect(other.find('.grMemberAttr[data-l1key=eqLogic_id]'), grEqItems(String(other.find('.gr-obj').val() || ''), grUsedEqIds(other)), '{{Équipement...}}', grRowFilter(other, 'eq'), current)
  })
}

/* Équipement changé : contrôle des doublons, objet automatique, chargement des commandes info */
function grRowEqChanged(_row) {
  var select = _row.find('.grMemberAttr[data-l1key=eqLogic_id]')
  var id = String(select.val() || '')
  var duplicate = _row.siblings('.gr-member').filter(function () {
    return String($(this).attr('data-eqLogic_id')) == id
  })
  if (id != '' && duplicate.length) {
    grAlert('{{Cet équipement est déjà présent dans cette commande}}')
    select.val('')
    id = ''
  }
  _row.attr('data-eqLogic_id', id)
  grRefreshSiblingEqs(_row)
  // Sans objet choisi, on sélectionne automatiquement celui de l'équipement
  var item = select.find('option:selected').data('item')
  if (id != '' && item && String(_row.find('.gr-obj').val() || '') == '') {
    grRowRenderObjects(_row, item.objectId)
    grFillSelect(select, grEqItems(item.objectId, grUsedEqIds(_row)), '{{Équipement...}}', grRowFilter(_row, 'eq'), id)
  }
  if (id == '') {
    grRowRenderCmds(_row, '')
    return
  }
  grFetchEqLogic(id).then(function () {
    grRowRenderCmds(_row, '')
    grApplyTemplate(_row)
  })
}

function grPick(_select, _filterFor) {
  var pick = $('<div class="gr-pick">')
  pick.append(_select)
  pick.append($('<input class="form-control input-sm gr-filter" placeholder="{{Filtre}}">').attr('data-for', _filterFor))
  return pick
}

function grAddMember(_state, _eqLogicId, _member) {
  _member = _member || {}
  _eqLogicId = String(init(_eqLogicId))
  var row = $('<div class="gr-row gr-member">').attr('data-eqLogic_id', _eqLogicId)
  row.append('<span class="gr-row-drag" title="{{Glisser pour réordonner}}"><i class="fas fa-grip-vertical"></i></span>')
  row.append(grPick($('<select class="form-control input-sm gr-obj">'), 'obj'))
  row.append(grPick($('<select class="grMemberAttr form-control input-sm" data-l1key="eqLogic_id">'), 'eq'))
  var isInfo = (_state.attr('data-type') != 'action')
  if (isInfo) {
    row.append(grPick($('<select class="grMemberAttr form-control input-sm" data-l1key="info_id">'), 'cmd'))
    var test = $('<div class="gr-pick-test">')
    test.append('<select class="grMemberAttr form-control input-sm" data-l1key="operator">' + grOperatorOptions(false) + '</select>')
    test.append('<input class="grMemberAttr form-control input-sm gr-test-value" data-l1key="value" placeholder="{{Valeur}}">')
    row.append(test)
  } else {
    row.append('<div class="gr-steps"><div class="gr-step-add"><a class="btn btn-default btn-xs bt_grAddStep" title="{{Enchaîner une autre commande de cet équipement}}"><i class="fas fa-plus"></i> {{Commande}}</a></div></div>')
  }
  row.append('<div class="gr-member-remove"><a class="btn btn-default btn-sm bt_grCloneMember" title="{{Cloner cette ligne pour un autre équipement}}"><i class="fas fa-clone"></i></a><a class="btn btn-default btn-sm bt_grRemoveMember" title="{{Retirer cet équipement}}"><i class="fas fa-times"></i></a></div>')
  _state.find('.gr-members').append(row)
  // Objet de l'équipement enregistré, puis équipement et commande info
  var eq = $.grep(grEqLogicList || [], function (e) { return String(e.id) == _eqLogicId })[0]
  var objectId = eq ? (String(init(eq.object_id)) == '' ? 'none' : String(eq.object_id)) : ''
  grRowRenderObjects(row, objectId)
  grFillSelect(row.find('.grMemberAttr[data-l1key=eqLogic_id]'), grEqItems(String(row.find('.gr-obj').val() || ''), grUsedEqIds(row)), '{{Équipement...}}', '', _eqLogicId)
  if (isInfo) {
    grRowRenderCmds(row, init(_member.info_id))
    row.find('.grMemberAttr[data-l1key=operator]').val(init(_member.operator, '=='))
    row.find('.grMemberAttr[data-l1key=value]').val(init(_member.value))
  } else {
    var actions = grMemberActions(_member)
    if (actions.length == 0) {
      actions = [{}]
    }
    for (var k in actions) {
      grAddStep(row, actions[k])
    }
    if (_eqLogicId != '' && row.find('.gr-step').filter(function () { return $(this).hasClass('gr-step-broken') }).length) {
      grMarkBroken(row, '{{Une commande action de cette ligne a été supprimée : choisissez-en une autre.}}')
    }
  }
  if (_eqLogicId != '' && String(row.find('.grMemberAttr[data-l1key=eqLogic_id]').val() || '') != _eqLogicId) {
    grMarkBroken(row, '{{L\'équipement de cette ligne a été supprimé : choisissez-en un autre ou retirez la ligne.}}')
  } else if (isInfo && init(_member.info_id) != '' && String(row.find('.grMemberAttr[data-l1key=info_id]').val() || '') == '') {
    grMarkBroken(row, '{{La commande info de cette ligne a été supprimée : choisissez-en une autre.}}')
  }
  grRefreshMembersEmpty(_state)
  return row
}

/* Info : valeur actuelle en indication */
function grUpdateMemberHints(_row) {
  var info = _row.find('.grMemberAttr[data-l1key=info_id] option:selected').data('item')
  _row.find('.grMemberAttr[data-l1key=value]').attr('placeholder', info ? '{{actuel :}} ' + init(info.value) : '{{Valeur}}')
}

/* Action : champ de valeur adapté au sous-type de la commande */
function grUpdateStepValue(_step, _actionValue) {
  var box = _step.find('.gr-pick-test')
  var current = init(_actionValue, box.find('.grStepAttr[data-l1key=actionValue]').val())
  box.empty()
  var cmd = _step.find('.grStepAttr[data-l1key=action_id] option:selected').data('item')
  if (!cmd) {
    grRefreshValueColumn(_step.closest('.gr-state'))
    return
  }
  var field = null
  switch (cmd.subType) {
    case 'slider':
      field = $('<input type="number" class="grStepAttr form-control input-sm gr-test-value" data-l1key="actionValue" placeholder="{{Valeur}}">')
      var min = init(cmd.minValue)
      var max = init(cmd.maxValue)
      if (min !== '') field.attr('min', min)
      if (max !== '') field.attr('max', max)
      // Plage de la commande en indication (ex. température de couleur : 153 à 500)
      var range = (min !== '' && max !== '') ? min + ' {{à}} ' + max : (min !== '' ? '{{min}} ' + min : (max !== '' ? '{{max}} ' + max : ''))
      if (range !== '') field.attr('placeholder', range).attr('title', range)
      break
    case 'color':
      field = $('<input type="color" class="grStepAttr form-control input-sm gr-test-value" data-l1key="actionValue">')
      break
    case 'select':
      field = $('<select class="grStepAttr form-control input-sm gr-test-value" data-l1key="actionValue">')
      $.each(String(init(cmd.listValue)).split(';'), function (i, entry) {
        if (entry == '') return
        var parts = entry.split('|')
        $('<option>').val(parts[0]).text(parts[1] || parts[0]).appendTo(field)
      })
      break
    case 'message':
      field = $('<input class="grStepAttr form-control input-sm gr-test-value" data-l1key="actionValue" placeholder="{{Message}}">')
      break
    default:
      // Commande sans valeur (ex. On / Off) : rien à saisir
      grRefreshValueColumn(_step.closest('.gr-state'))
      return
  }
  box.append(field)
  if (current !== '') {
    field.val(current)
  }
  grRefreshValueColumn(_step.closest('.gr-state'))
}

/* Commande action : masque la colonne "Valeur" si aucune ligne n'en a besoin */
function grRefreshValueColumn(_state) {
  if (!_state || !_state.length || _state.attr('data-type') != 'action') {
    return
  }
  _state.toggleClass('gr-hide-value', _state.find('.grStepAttr[data-l1key=actionValue]').length == 0)
}

function grRefreshMembersEmpty(_state) {
  var count = _state.find('.gr-member').length
  var empty = count == 0
  _state.find('.gr-members-count').text(empty ? '' : count + (count > 1 ? ' {{équipements}}' : ' {{équipement}}'))
  _state.find('.gr-members-empty').toggle(empty)
  _state.find('.gr-members-head').toggle(!empty)
}

function grCollectStates() {
  return $('#div_grStates').children('.gr-state').map(function () {
    return grCollectState($(this))
  }).get()
}

function grCollectState(_state) {
  var item = null
  _state.first().each(function () {
    var type = $(this).attr('data-type') == 'action' ? 'action' : 'info'
    item = {
      id: $(this).find('.grStateAttr[data-l1key=id]').value(),
      type: type,
      name: $(this).find('.grStateAttr[data-l1key=name]').value(),
      members: []
    }
    if (type == 'info') {
      item.rule = $(this).find('.grStateAttr[data-l1key=rule]').value()
      item.ruleCount = $(this).find('.grStateAttr[data-l1key=ruleCount]').value()
      item.valueTrue = $(this).find('.grStateAttr[data-l1key=valueTrue]').value()
      item.valueFalse = $(this).find('.grStateAttr[data-l1key=valueFalse]').value()
    } else {
      item.pause = $(this).find('.grStateAttr[data-l1key=pause]').value()
    }
    $(this).find('.gr-member').each(function () {
      var member = $(this).getValues('.grMemberAttr')[0]
      // Lignes incomplètes conservées : l'enregistrement indique alors laquelle corriger
      if ($(this).hasClass('gr-scen')) {
        var scen = { type: 'scenario', scenario_id: init(member.scenario_id) }
        if (type == 'action') {
          scen.scenarioAction = init(member.scenarioAction, 'start')
        } else {
          scen.scenarioState = init(member.scenarioState, 'active')
        }
        item.members.push(scen)
        return
      }
      if (type == 'action') {
        member.actions = $(this).find('.gr-step').map(function () {
          return $(this).getValues('.grStepAttr')[0]
        }).get()
      }
      item.members.push(member)
    })
  })
  return item
}

/* ---------- Scénarios (commandes action) ---------- */

var GR_SCENARIO_ACTIONS = [
  { value: 'start', label: '{{Lancer}}' },
  { value: 'stop', label: '{{Arrêter}}' },
  { value: 'activate', label: '{{Activer}}' },
  { value: 'deactivate', label: '{{Désactiver}}' }
]

var GR_SCENARIO_STATES = [
  { value: 'active', label: '{{est actif}}' },
  { value: 'inactive', label: '{{est inactif}}' },
  { value: 'running', label: '{{est en cours}}' },
  { value: 'stopped', label: '{{est arrêté}}' }
]

var grScenarioList = null

function grLoadScenarioList() {
  var deferred = $.Deferred()
  if (grScenarioList !== null) {
    return deferred.resolve(grScenarioList).promise()
  }
  $.ajax({
    type: 'POST',
    url: GR_AJAX_URL,
    data: { action: 'getScenarios' },
    dataType: 'json',
    global: false,
    error: function () {
      deferred.resolve([])
    },
    success: function (data) {
      grScenarioList = (data.state == 'ok') ? data.result : []
      deferred.resolve(grScenarioList)
    }
  })
  return deferred.promise()
}

function grScenGroupItems() {
  var seen = {}
  var items = []
  $.each(grScenarioList || [], function (i, sc) {
    if (!seen[sc.group]) {
      seen[sc.group] = true
      items.push({ id: sc.group, name: sc.group })
    }
  })
  return items
}

function grScenItems(_group) {
  return $.map($.grep(grScenarioList || [], function (sc) { return _group == '' || sc.group == _group }), function (sc) {
    return { id: String(sc.id), name: (_group == '' ? '[' + sc.group + '] ' : '') + sc.name + (sc.isActive == 1 ? '' : ' ({{inactif}})'), group: sc.group }
  })
}

function grScenRenderGroups(_row, _current) {
  grFillSelect(_row.find('.gr-scen-group'), grScenGroupItems(), '{{Tous les groupes}}', grRowFilter(_row, 'sgroup'), _current)
}

function grScenRenderScenarios(_row, _current) {
  grFillSelect(_row.find('.grMemberAttr[data-l1key=scenario_id]'), grScenItems(String(_row.find('.gr-scen-group').val() || '')), '{{Scénario...}}', grRowFilter(_row, 'scen'), _current)
}

/* Ligne "scénario" d'une commande action : Groupe > Scénario > Lancer / Arrêter / Activer / Désactiver */
function grAddScenarioMember(_state, _member) {
  _member = _member || {}
  var row = $('<div class="gr-row gr-member gr-scen">')
  row.append('<span class="gr-row-drag" title="{{Glisser pour réordonner}}"><i class="fas fa-grip-vertical"></i></span>')
  row.append(grPick($('<select class="form-control input-sm gr-scen-group">'), 'sgroup'))
  row.append(grPick($('<select class="grMemberAttr form-control input-sm" data-l1key="scenario_id">'), 'scen'))
  var isInfo = (_state.attr('data-type') != 'action')
  var action = $('<div class="gr-scen-action">')
  action.append('<span class="groupir-badge is-scenario"><i class="fas fa-cogs"></i> {{Scénario}}</span>')
  var select = $('<select class="grMemberAttr form-control input-sm">').attr('data-l1key', isInfo ? 'scenarioState' : 'scenarioAction')
  $.each(isInfo ? GR_SCENARIO_STATES : GR_SCENARIO_ACTIONS, function (i, a) {
    $('<option>').val(a.value).text(a.label).appendTo(select)
  })
  action.append(select)
  row.append(action)
  row.append('<div class="gr-member-remove"><a class="btn btn-default btn-sm bt_grRemoveMember" title="{{Retirer ce scénario}}"><i class="fas fa-times"></i></a></div>')
  _state.find('.gr-members').append(row)
  var scenarioId = String(init(_member.scenario_id))
  var sc = $.grep(grScenarioList || [], function (x) { return String(x.id) == scenarioId })[0]
  grScenRenderGroups(row, sc ? sc.group : '')
  grScenRenderScenarios(row, scenarioId)
  select.val(isInfo ? init(_member.scenarioState, 'active') : init(_member.scenarioAction, 'start'))
  if (scenarioId != '' && String(row.find('.grMemberAttr[data-l1key=scenario_id]').val() || '') != scenarioId) {
    grMarkBroken(row, '{{Le scénario de cette ligne a été supprimé : choisissez-en un autre ou retirez la ligne.}}')
  }
  grRefreshMembersEmpty(_state)
  return row
}

$('#grouptab').off('click', '.bt_grAddMemberScenario').on('click', '.bt_grAddMemberScenario', function () {
  var state = $(this).closest('.gr-state')
  grSetCollapsed(state, false)
  grLoadScenarioList().then(function () {
    grAddScenarioMember(state, {}).find('.grMemberAttr[data-l1key=scenario_id]').focus()
  })
})

$('#grouptab').off('change', '.gr-scen-group').on('change', '.gr-scen-group', function () {
  grScenRenderScenarios($(this).closest('.gr-member'))
})

/* Scénario choisi sans groupe : on sélectionne automatiquement son groupe */
$('#grouptab').off('change', '.gr-scen .grMemberAttr[data-l1key=scenario_id]').on('change', '.gr-scen .grMemberAttr[data-l1key=scenario_id]', function () {
  var row = $(this).closest('.gr-member')
  var item = $(this).find('option:selected').data('item')
  if (item && String(row.find('.gr-scen-group').val() || '') == '') {
    var id = String($(this).val())
    grScenRenderGroups(row, item.group)
    grScenRenderScenarios(row, id)
  }
})

/* ---------- Lignes cassées (équipement, commande ou scénario supprimé) ---------- */

function grMarkBroken(_row, _message) {
  _row.addClass('gr-broken')
  _row.find('.gr-broken-msg').remove()
  _row.append($('<div class="gr-broken-msg">').append('<i class="fas fa-exclamation-triangle"></i> ').append($('<span>').text(_message)))
}

function grClearBroken(_row) {
  _row.removeClass('gr-broken').find('.gr-broken-msg').remove()
  _row.find('.gr-step-broken').removeClass('gr-step-broken')
}

$('#grouptab').off('change', '.gr-broken select').on('change', '.gr-broken select', function () {
  grClearBroken($(this).closest('.gr-member'))
})

/* ---------- Règle d'une commande info (tous / au moins un / au moins N) ---------- */

function grRefreshRule(_state) {
  var atleast = _state.find('.gr-rule').val() == 'atleast'
  var count = _state.find('.gr-rule-count')
  count.toggle(atleast)
  if (atleast && init(count.val()) == '') {
    count.val(1)
  }
}

$('#grouptab').off('change', '.gr-rule').on('change', '.gr-rule', function () {
  grRefreshRule($(this).closest('.gr-state'))
})

/* ---------- Réordonner : commandes, lignes et actions (glisser-déposer) ---------- */

function grMakeSortable(_state) {
  if (typeof $.fn.sortable !== 'function') {
    return
  }
  _state.find('.gr-members').sortable({ handle: '.gr-row-drag', items: '> .gr-member', axis: 'y', tolerance: 'pointer', forcePlaceholderSize: true, placeholder: 'gr-sort-placeholder' })
  _state.find('.gr-steps').each(function () {
    $(this).sortable({ handle: '.gr-step-drag', items: '> .gr-step', axis: 'y', tolerance: 'pointer', forcePlaceholderSize: true, placeholder: 'gr-sort-placeholder' })
  })
}

function grMakeStatesSortable() {
  if (typeof $.fn.sortable !== 'function') {
    $('#grouptab').addClass('gr-no-sort')
    return
  }
  $('#div_grStates').sortable({ handle: '.gr-drag', items: '> .gr-state', axis: 'y', tolerance: 'pointer', forcePlaceholderSize: true, placeholder: 'gr-sort-placeholder' })
}

/* ---------- Clonage ---------- */

/* Nom de la commande choisie dans une liste (pour la retrouver sur un autre équipement) */
function grSelectedName(_select) {
  return init(_select.val()) == '' ? '' : _select.find('option:selected').text()
}

function grCmdIdByName(_eqLogicId, _type, _name) {
  if (init(_name) == '') {
    return ''
  }
  var found = $.grep(grCmdItems(_eqLogicId, _type), function (c) { return c.name == _name })[0]
  return found ? found.id : ''
}

/*
 * Clone une ligne juste en dessous : même objet, équipement à choisir.
 * Une fois l'équipement choisi, ses commandes portant les mêmes noms sont sélectionnées, avec les mêmes valeurs.
 */
function grCloneMember(_row) {
  var state = _row.closest('.gr-state')
  var template = { objectId: String(_row.find('.gr-obj').val() || '') }
  if (grRowType(_row) == 'info') {
    template.info = grSelectedName(_row.find('.grMemberAttr[data-l1key=info_id]'))
    template.operator = _row.find('.grMemberAttr[data-l1key=operator]').val()
    template.value = _row.find('.grMemberAttr[data-l1key=value]').val()
  } else {
    template.steps = _row.find('.gr-step').map(function () {
      return { name: grSelectedName($(this).find('.grStepAttr[data-l1key=action_id]')), actionValue: init($(this).find('.grStepAttr[data-l1key=actionValue]').val()) }
    }).get()
  }
  var clone = grAddMember(state, '', { operator: template.operator, value: template.value })
  clone.insertAfter(_row)
  if (template.steps) {
    // Toutes les actions de la ligne d'origine, dans l'ordre (commande à retrouver sur le nouvel équipement)
    clone.find('.gr-step').remove()
    var steps = template.steps.length ? template.steps : [{}]
    for (var i in steps) {
      grAddStep(clone, steps[i])
    }
  }
  clone.data('grTemplate', template)
  if (template.objectId != '') {
    grRowRenderObjects(clone, template.objectId)
    grRowRenderEqs(clone)
  }
  grRefreshSiblingEqs(null, state)
  clone.find('.grMemberAttr[data-l1key=eqLogic_id]').focus()
  return clone
}

/* Applique le modèle d'une ligne clonée une fois son équipement choisi */
function grApplyTemplate(_row) {
  var template = _row.data('grTemplate')
  var eqId = String(init(_row.attr('data-eqLogic_id')))
  if (!template || eqId == '') {
    return
  }
  _row.removeData('grTemplate')
  if (grRowType(_row) == 'info') {
    grRowRenderCmds(_row, grCmdIdByName(eqId, 'info', template.info))
    _row.find('.grMemberAttr[data-l1key=operator]').val(init(template.operator, '=='))
    _row.find('.grMemberAttr[data-l1key=value]').val(init(template.value))
    return
  }
  // Action : les commandes sont déjà retrouvées par leur nom dans grRowRenderCmds
}

/* Clone une commande du groupe (carte) juste en dessous, avec ses équipements, commandes et valeurs */
function grCloneState(_state) {
  var data = grCollectState(_state)
  data.id = ''
  data.name = init(data.name) == '' ? '' : data.name + ' ({{copie}})'
  var clone = grAddState(data)
  clone.insertAfter(_state)
  grSetCollapsed(clone, false)
  clone.find('.grStateAttr[data-l1key=name]').focus().select()
  return clone
}

$('#grouptab').off('click', '.bt_grToggleState').on('click', '.bt_grToggleState', function () {
  var state = $(this).closest('.gr-state')
  grSetCollapsed(state, !state.hasClass('gr-collapsed'))
})

$('#grouptab').off('click', '.bt_grCollapseAll').on('click', '.bt_grCollapseAll', function () {
  var collapse = $(this).attr('data-collapse') == '1'
  $('#div_grStates').children('.gr-state').each(function () {
    grSetCollapsed($(this), collapse)
  })
})

$('#grouptab').off('click', '.bt_grAddMemberEq').on('click', '.bt_grAddMemberEq', function () {
  var state = $(this).closest('.gr-state')
  grSetCollapsed(state, false)
  grLoadEqLogicList().then(function () {
    grAddMember(state, '', {})
    grMakeSortable(state)
  })
})

$('#grouptab').off('change', '.gr-obj').on('change', '.gr-obj', function () {
  grRowRenderEqs($(this).closest('.gr-member'))
})

$('#grouptab').off('change', '.grMemberAttr[data-l1key=eqLogic_id]').on('change', '.grMemberAttr[data-l1key=eqLogic_id]', function () {
  grRowEqChanged($(this).closest('.gr-member'))
})

$('#grouptab').off('input', '.gr-filter').on('input', '.gr-filter', function () {
  var row = $(this).closest('.gr-member')
  switch ($(this).attr('data-for')) {
    case 'obj':
      var before = String(row.find('.gr-obj').val() || '')
      grRowRenderObjects(row)
      if (String(row.find('.gr-obj').val() || '') != before) {
        grRowRenderEqs(row)
      }
      break
    case 'eq':
      grRowRenderEqs(row)
      break
    case 'sgroup':
      var beforeGroup = String(row.find('.gr-scen-group').val() || '')
      grScenRenderGroups(row)
      if (String(row.find('.gr-scen-group').val() || '') != beforeGroup) {
        grScenRenderScenarios(row)
      }
      break
    case 'scen':
      grScenRenderScenarios(row)
      break
    case 'cmd':
      var step = $(this).closest('.gr-step')
      if (step.length) {
        grStepRenderCmds(step)
      } else {
        grRowRenderCmds(row)
      }
      break
  }
})

$('#grouptab').off('click', '.bt_grCloneMember').on('click', '.bt_grCloneMember', function () {
  var row = $(this).closest('.gr-member')
  grCloneMember(row)
  grMakeSortable(row.closest('.gr-state'))
})

$('#grouptab').off('click', '.bt_grConfigureCmd').on('click', '.bt_grConfigureCmd', function () {
  var cmdId = init($(this).attr('data-cmd_id'))
  if (cmdId == '') {
    $('#div_alert').showAlert({ message: '{{Enregistrez le groupe pour configurer cette commande.}}', level: 'warning' })
    return
  }
  grOpenCmdConfigure(cmdId)
})

$('#grouptab').off('click', '.bt_grCloneState').on('click', '.bt_grCloneState', function () {
  grCloneState($(this).closest('.gr-state'))
})

$('#grouptab').off('click', '.bt_grRemoveMember').on('click', '.bt_grRemoveMember', function () {
  var state = $(this).closest('.gr-state')
  $(this).closest('.gr-member').remove()
  grRefreshSiblingEqs(null, state)
  grRefreshValueColumn(state)
  grRefreshMembersEmpty(state)
})

$('.bt_grAddGroupCmd').off('click').on('click', function () {
  var type = $(this).attr('data-type')
  grLoadEqLogicList().then(function () {
    grAddState({ type: type }).find('.grStateAttr[data-l1key=name]').focus()
  })
})

$('#grouptab').off('click', '.bt_grRemoveState').on('click', '.bt_grRemoveState', function () {
  $(this).closest('.gr-state').remove()
  grRefreshEmpty()
})

$('#grouptab').off('change', '.grMemberAttr[data-l1key=info_id]').on('change', '.grMemberAttr[data-l1key=info_id]', function () {
  grUpdateMemberHints($(this).closest('.gr-member'))
})

$('#grouptab').off('change', '.grStepAttr[data-l1key=action_id]').on('change', '.grStepAttr[data-l1key=action_id]', function () {
  var step = $(this).closest('.gr-step')
  step.data('grName', grSelectedName($(this))).data('grValue', '')
  grUpdateStepValue(step, '')
})

/* Valeur d'un curseur : ramenée dans la plage de la commande (min / max) */
function grClampValue(_field) {
  var raw = String(_field.val() || '').trim()
  if (raw === '') {
    return
  }
  var value = parseFloat(raw.replace(',', '.'))
  if (isNaN(value)) {
    _field.val('')
    return
  }
  var min = parseFloat(_field.attr('min'))
  var max = parseFloat(_field.attr('max'))
  var clamped = value
  if (!isNaN(min) && clamped < min) clamped = min
  if (!isNaN(max) && clamped > max) clamped = max
  if (clamped !== value) {
    _field.val(clamped)
    _field.addClass('gr-value-clamped')
    setTimeout(function () { _field.removeClass('gr-value-clamped') }, 1200)
  }
}

$('#grouptab').off('change blur', 'input[type=number].gr-test-value').on('change blur', 'input[type=number].gr-test-value', function () {
  grClampValue($(this))
})

$('#grouptab').off('click', '.bt_grAddStep').on('click', '.bt_grAddStep', function () {
  var row = $(this).closest('.gr-member')
  grAddStep(row, {}).find('.grStepAttr[data-l1key=action_id]').focus()
})

$('#grouptab').off('click', '.bt_grRemoveStep').on('click', '.bt_grRemoveStep', function () {
  var row = $(this).closest('.gr-member')
  if (row.find('.gr-step').length <= 1) {
    return
  }
  $(this).closest('.gr-step').remove()
  grRefreshSteps(row)
  grRefreshValueColumn(row.closest('.gr-state'))
})

/* ---------- Scénarios liés (onglet Général) ---------- */

function grLinkedList(_title, _icon, _items, _empty) {
  var html = '<div class="groupir-linked-title"><i class="fas ' + _icon + '"></i> ' + _title + ' <span class="groupir-count">' + _items.length + '</span></div>'
  if (_items.length == 0) {
    return html + '<div class="groupir-meta groupir-linked-empty">' + _empty + '</div>'
  }
  html += '<div class="groupir-linked-list">'
  $.each(_items, function (i, sc) {
    html += '<a class="groupir-linked-item' + (sc.isActive == 1 ? '' : ' is-inactive') + '" href="index.php?v=d&p=scenario&id=' + encodeURIComponent(sc.id) + '" target="_blank" title="{{Ouvrir le scénario}}">'
    html += '<span class="groupir-linked-name"></span>'
    if (sc.by) html += '<span class="groupir-badge is-scenario groupir-linked-by"></span>'
    if (sc.isActive != 1) html += '<span class="groupir-meta">{{inactif}}</span>'
    html += '<i class="fas fa-external-link-alt"></i></a>'
  })
  return html + '</div>'
}

function grLoadLinkedScenarios(_eqLogicId) {
  var box = $('#div_grLinkedScenarios')
  if (init(_eqLogicId) == '') {
    box.html('<div class="groupir-meta">{{Enregistrez le groupe pour voir ses scénarios liés.}}</div>')
    return
  }
  box.html('<div class="groupir-meta"><i class="fas fa-spinner fa-spin"></i> {{Chargement...}}</div>')
  $.ajax({
    type: 'POST',
    url: GR_AJAX_URL,
    data: { action: 'getLinkedScenarios', id: _eqLogicId },
    dataType: 'json',
    global: false,
    error: function () {
      box.html('<div class="groupir-meta">{{Impossible de charger les scénarios liés.}}</div>')
    },
    success: function (data) {
      if (data.state != 'ok') {
        box.html('<div class="groupir-meta">{{Impossible de charger les scénarios liés.}}</div>')
        return
      }
      var using = data.result.using || []
      var piloted = data.result.piloted || []
      box.html(grLinkedList('{{Utilisent ce groupe}}', 'fa-sign-in-alt', using, '{{Aucun scénario n\'utilise les commandes de ce groupe.}}')
        + grLinkedList('{{Pilotés par ce groupe}}', 'fa-play-circle', piloted, '{{Aucun scénario dans les commandes action.}}'))
      // Textes insérés sans interprétation HTML
      var items = using.concat(piloted)
      box.find('.groupir-linked-item').each(function (i) {
        $(this).find('.groupir-linked-name').text(items[i].name)
        $(this).find('.groupir-linked-by').text(init(items[i].by))
      })
    }
  })
}

/* ============================================================
 * Hooks appelés par le core (plugin.template.js)
 * ============================================================ */
function printEqLogic(_eqLogic) {
  $('#div_grStates').empty()
  grCmdIdsByLogical = {}
  $.each(_eqLogic.cmd || [], function (i, cmd) {
    if (init(cmd.logicalId) != '') grCmdIdsByLogical[cmd.logicalId] = cmd.id
  })
  grLoadLinkedScenarios(init(_eqLogic.id))
  grMakeStatesSortable()
  var commands = (_eqLogic.configuration || {}).commands || []
  // Charge la liste des équipements et les commandes de ceux utilisés avant d'afficher les correspondances
  grGroupEqCache = {}
  grEqLogicList = null
  var eqIds = []
  for (var i in commands) {
    for (var j in commands[i].members || []) {
      var id = String(init(commands[i].members[j].eqLogic_id))
      if (id != '' && eqIds.indexOf(id) < 0) {
        eqIds.push(id)
      }
    }
  }
  var loads = $.map(eqIds, function (id) { return grFetchEqLogic(id) })
  loads.push(grLoadEqLogicList())
  grScenarioList = null
  loads.push(grLoadScenarioList())
  $.when.apply($, loads).always(function () {
    for (var i in commands) {
      grAddState(commands[i])
    }
    grRefreshEmpty()
  })
  grRefreshEmpty()
}

function saveEqLogic(_eqLogic) {
  if (!isset(_eqLogic.configuration)) {
    _eqLogic.configuration = {}
  }
  $('#grouptab input[type=number].gr-test-value').each(function () {
    grClampValue($(this))
  })
  _eqLogic.configuration.commands = grCollectStates()
  return _eqLogic
}

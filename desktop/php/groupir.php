<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('groupir');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter un groupe}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>

		<?php if (count($eqLogics) > 0) { ?>
		<div class="input-group groupir-search">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn roundedRight" title="{{Effacer}}"><i class="fas fa-times"></i></a>
			</div>
		</div>
		<?php } ?>

		<legend><i class="fas fa-layer-group"></i> {{Mes groupes}} <span class="groupir-count"><?php echo count($eqLogics); ?></span></legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<div class="groupir-empty"><i class="fas fa-layer-group"></i>{{Aucun groupe pour le moment.}}<br><span class="groupir-meta">{{Cliquez sur "Ajouter un groupe" pour commencer.}}</span></div>';
		} else {
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page du groupe -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="gr-unsaved" style="display:none;" title="{{Des modifications n'ont pas été sauvegardées}}"><i class="fas fa-exclamation-circle"></i><span class="hidden-xs"> {{Modifications non enregistrées}}</span></span>
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Général}}</a></li>
			<li role="presentation"><a href="#grouptab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-layer-group"></i> {{Correspondances}}</a></li>
		</ul>
		<div class="tab-content">

			<!-- ===================== Onglet Général ===================== -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<div class="groupir-section"><i class="fas fa-wrench"></i><span>{{Paramètres généraux}}</span></div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom du groupe}}</label>
								<div class="col-sm-7">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{ex. Ampoules salon}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-7">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-7">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-7">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>
						</div>

						<div class="col-lg-6">
							<div class="groupir-section"><i class="fas fa-info-circle"></i><span>{{Informations}}</span></div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-7">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment" placeholder="{{Optionnel}}"></textarea>
								</div>
							</div>
							<div class="groupir-section"><i class="fas fa-cogs"></i><span>{{Scénarios liés}}</span></div>
							<div class="form-group">
								<div class="col-sm-offset-1 col-sm-10">
									<div id="div_grLinkedScenarios" class="groupir-linked"></div>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- ===================== Onglet Correspondances ===================== -->
			<div role="tabpanel" class="tab-pane" id="grouptab">
				<div class="groupir-toolbar">
					<div class="groupir-section groupir-section--inline"><i class="fas fa-layer-group"></i><span>{{Commandes du groupe}}</span> <span class="gr-group-name" id="span_grName"></span> <span class="groupir-count" id="span_grCount">0</span></div>
					<div class="groupir-spacer"></div>
					<a class="btn btn-default btn-sm roundedLeft bt_grCollapseAll" data-collapse="1" title="{{Tout plier}}"><i class="fas fa-compress-alt"></i>
					</a><a class="btn btn-default btn-sm roundedRight bt_grCollapseAll" data-collapse="0" title="{{Tout déplier}}"><i class="fas fa-expand-alt"></i></a>
					<a class="btn btn-info btn-sm roundedLeft bt_grAddGroupCmd" data-type="info"><i class="fas fa-eye"></i> {{Commande info}}
					</a><a class="btn btn-warning btn-sm roundedRight bt_grAddGroupCmd" data-type="action"><i class="fas fa-hand-pointer"></i> {{Commande action}}</a>
				</div>
				<div id="div_grStates"></div>
				<div class="gr-empty groupir-empty" data-for="div_grStates">
					<i class="fas fa-layer-group"></i>{{Aucune commande pour ce groupe.}}<br>
					<span class="groupir-meta">{{Ajoutez par exemple les commandes info "on" et "off", et les commandes action "Allumer" et "Éteindre".}}</span>
				</div>
			</div><!-- /.tabpanel #grouptab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<?php include_file('desktop', 'groupir', 'css', 'groupir'); ?>
<?php include_file('desktop', 'groupir', 'js', 'groupir'); ?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>

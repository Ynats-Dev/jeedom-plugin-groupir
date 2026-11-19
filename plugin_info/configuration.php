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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<?php include_file('desktop', 'groupir', 'css', 'groupir'); ?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <div class="col-lg-offset-1 col-lg-9">
        <div class="groupir-callout">
          <i class="fas fa-info-circle"></i>
          <div>
            <p><strong>{{Aucune configuration globale nécessaire}}</strong></p>
            <p>{{Chaque groupe se configure depuis son équipement, dans l'onglet Correspondances.}}</p>
          </div>
        </div>
      </div>
    </div>
  </fieldset>
</form>


<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

script('carnet', [
	'settings-admin'
]);

/** @var \OCP\IL10N $l */
/** @var array $_ */
?>
<form id="Carnet" class="section">
    <h2>
        <?php p($l->t('Carnet')); ?>
    </h2>
    <p>
        <input type="checkbox" name="carnet_display_fullscreen" id="carnetDisplayFullscreen" class="checkbox" <?php
            ($_['carnet_display_fullscreen']==='yes' ) ? print_unescaped('checked="checked"') : null ?>/>
		<label for="carnetDisplayFullscreen"><?php p($l->t('
            Display fullscreen as a standalone app')); ?></label>
        <br>
        <em>
            <?php p($l->t('To use only if you provide a service with only Carnet')); ?></em>
    </p>

</form>
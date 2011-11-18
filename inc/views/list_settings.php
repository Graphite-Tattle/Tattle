<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$active_tab_alerts = " class=active";
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	?>
<table class="zebra-striped">
          <thead>
		<tr>
    <th>Name</th>
    <th>Value</th>
    <th>Default Value</th>
    </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($list_plugin_settings as $setting_name => $setting) {
		?>
    	<tr>
        <td><?=$setting['friendly_name']; ?></td>
        <td><?=(isset($setting['value']) ? $setting['value'] : 'Default'); ?></td>
        <td><?=$setting['default']; ?></td>
        <td><?php
             if (!isset($owner_id)) {
               $owner_id = NULL;
             }
             if (isset($setting['value'])) {
               echo '<a href="' . Setting::makeURL('edit',$setting_type,$setting_name,$owner_id) . '">Update</a> | ';
               echo '<a href="' . Setting::makeURL('delete',$setting_type,$setting_name,$owner_id) . '">Delete</a>';
             } else {
               echo '<a href="' . Setting::makeURL('add',$setting_type,$setting_name,$owner_id) . '">Override</a>'; 
             } ?>
         </td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">No settings? uh-oh</p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>

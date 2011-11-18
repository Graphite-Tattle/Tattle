<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$active_tab_alerts = " class=active";
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$users->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
<table class="zebra-striped">
          <thead>
		<tr>
    <th>Username</th>
    <th>Email</th>
    <th>Action</th>
       </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($users as $user) {
		?>
    	<tr>
        <td><?=$user->prepareUsername(); ?></td>
        <td><?=$user->prepareEmail(); ?></td>
        <td><?php if (fSession::get('user_id') == $user->getUserId() || fAuthorization::checkAuthLevel('admin')) { 
                     echo '<a href="' . User::makeUrl('edit',$user) . '">Edit</a> | '; 
                     echo '<a href="' . Setting::makeURL('list','user',NULL,$user->getUserId()) . '">Settings</a>';
                   } ?>
       <?php if (fAuthorization::checkAuthLevel('admin') && $user->getUserId() != 1) {
           ?> <a href="<?=User::makeUrl('delete',$user); ?>">Delete</a></td>
       <?php } ?>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle users? <a href="<?=User::makeUrl('add'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>

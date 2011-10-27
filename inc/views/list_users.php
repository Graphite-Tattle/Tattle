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
        <td><?php echo $user->prepareUsername() ?></td>
        <td><?php echo $user->prepareEmail() ?></td>
        <td><?php if (fSession::get('user_id') == $user->getUserId() || fSession::get('user_id') == 1) { echo '<a href="' . User::makeUrl('edit',$user) . '">Edit</a>'; } ?>
       <?php if ($user->getUserId() != 1 && fSession::get('user_id') == 1) {
           ?> <a href="<?php echo User::makeUrl('delete',$user); ?>">Delete</a></td>
       <?php } ?>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Graphite-Tattle users? <a href="<?php echo '<a href="'. User::makeUrl('add'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>

<?php
$tmpl->set('title', 'Manage Meetups');
$tmpl->place('header');
?>

<h1><?php echo $tmpl->prepare('title') ?></h1>

<p class="nav">
	<a href="<?php echo Meetup::makeURL('add') ?>">Add a new meetup</a>
</p>

<?php
fMessaging::show('error', fURL::get());
fMessaging::show('success', fURL::get());
?>
<div id="main">
<?php
try {
	$meetups->tossIfEmpty();
	
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
	<table class="zebra-striped" >
		<tr>
			<th><?php fCRUD::printSortableColumn('date', 'Date') ?></th>
			<th><?php fCRUD::printSortableColumn('location', 'Location') ?></th>
			<th>Actions</th>
		</tr>
		<?php
		foreach ($meetups as $meetup) {
			?>
			<tr class="<?php echo fCRUD::getRowClass($meetup->getDate()->__toString(), $affected) ?>">
				<td class="<?php echo fCRUD::getColumnClass('date') ?>">
					<?php echo $meetup->prepareDate('F j, Y') ?>
				</td>
				<td class="<?php echo fCRUD::getColumnClass('location') ?>">
					<?php echo $meetup->prepareVenue() ?> -
					<?php echo $meetup->prepareCity() ?>,
					<?php echo $meetup->prepareState() ?>
				</td>
				<td class="actions">
					<a href="<?php echo Meetup::makeURL('edit', $meetup) ?>">Edit</a> |
					<a href="<?php echo Meetup::makeURL('delete', $meetup) ?>">Delete</a>
				</td>
			</tr>
			<?php	
		}
		?>
	</table>
	<?php
	
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no meetups. <a href="<?php echo Meetup::makeURL('add') ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
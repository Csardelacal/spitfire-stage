
<?php
	if (isset($_GET['lang'])) lang($_GET['lang']);
?>

<?php lang()->start('es'); ?>
	<h1>Este texto está en español</h1>
<?php lang()->end(); ?>

<?php lang()->start('en'); ?>
	This text is english
<?php lang()->end(); ?>

<?php lang()->start('de'); ?>
	Dieser text ist deutsch
<?php lang()->end(); ?>

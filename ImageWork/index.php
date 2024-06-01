<?php 
include('ImageWork.php');
ImageWork::resize_crop("upload", __DIR__.'/uploads/', 500, 500);
?>
<form method="POST" action="" enctype="multipart/form-data">
	<input type="file" name="upload" />
	<button type="submit">Salvar</button>
</form>

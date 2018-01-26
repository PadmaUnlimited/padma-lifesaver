<?php

defined('ABSPATH') or die( 'Access Forbidden!' );


$lifeSaver = new padmaLifeSaver();


$source         = $lifeSaver->getSource();
$source_label   = ucfirst($source);
$templates      = $lifeSaver->getTemplates();
$nonce          = wp_create_nonce('lifesaver_nonce');

echo $lifeSaver->alertBox( $source_label . ' detected','warning');

if($_POST){
    $converter = new padmaConverter($source);
    $converter->plugins_loaded();
    $converter->after_setup_theme();
    $converter->admin_notices();
    $converter->setPadma();

}
if($_GET['lifesaver-convert'] == 'complete'){
    echo $lifeSaver->alertBox($source_label . ' to Padma conversion completed','success');
}

debug($_GET);
debug($_REQUEST);


?>
<div class="wrap">
    <h1>Headway or BloxTheme to Padma</h1>
    <hr class="wp-header-end">
    <br />
    <p></p>
    <form method="POST">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
        <div>
            <input type="checkbox" id="widgets" name="widgets" checked="checked" /><label for="widgets" style="vertical-align: top;">Include widgets</label>
        </div>
        <br />
        <div>
            <label for="template"><?php echo $source_label; ?> Template:</label>
            <select id="template" name="template">
                <?php foreach($templates as $template): ?>
                <option value="<?php echo $template['id']; ?>"><?php echo $template['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <br />
        <input class="button button-primary" type="submit" name="lifesaver" value="Start Conversion" <?php if(!$lifeSaver->dirValidation()): ?>disabled="disabled"<?php endif; ?> />
    </form>
</div>
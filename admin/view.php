<?php

// The first time the plugin initiate it create two options
function contexto_setting_section_callback(){

  $default = array(
                    "contexto_options_apikey" => "6e3a64e825ce0eab61fe3801e190d41ea2794f345a61b4df315aa6b1",
                    "contexto_options_confidence" => "1"
                  );
  add_option('contexto_options_data', $default);

}

// Function to display the API input
function contexto_setting_apikey_callback() {
	$options = get_option('contexto_options_data');
	echo "<input id='contexto_options_apikey' name='contexto_options_data[contexto_options_apikey]' type='text' value='{$options['contexto_options_apikey']}' size='100'/>";


  echo "<p>In order to make this plugin work, you need to register with <a href='https://www.textrazor.com/' target='_blank'>TextRazor</a>. Then you will receive a
  key by email. Place the API key below and save it.</p>";
}


// Function to display the Confidence input
function contexto_setting_confidence_callback() {
	$options = get_option('contexto_options_data');
  for ($i=1; $i <= 10; $i++) {
    if ($options['contexto_options_confidence'] == $i) {
      echo " <input type='radio' name='contexto_options_data[contexto_options_confidence]' value='{$i}' checked='checked'> {$i}";
    } else {
      echo " <input type='radio' name='contexto_options_data[contexto_options_confidence]' value='{$i}'> {$i}";
    }
  }
  echo "<p>The confidence that TextRazor is correct that this is a valid entity. TextRazor uses an ever increasing number of signals to help spot valid entities, all of which contribute to this score.</p>

  <p>These include the semantic agreement between the context in the source text and our knowledgebase, compatibility between other entities in the text, compatibility between the expected entity type and context, prior probabilities of having seen this entity across wikipedia and other web datasets.</p>

  <p>The score ranges from 0.5 to 10, with 10 representing the highest confidence that this is a valid entity - More info: <a href='https://www.textrazor.com/docs/php' target='_blank'>TextRazor Docs</a></p>";
}
?>

<!-- Html -->
<div class="wrap">
  <h1><?= esc_html(get_admin_page_title()); ?></h1>
  <h2>Documentation of Contexto Plugin</h2>
  <form method="post" action="options.php">
  <?php
  // Output nonce, action, and option_page fields for a settings page. Must be inside of the form tag for the options page.
  settings_fields( 'contexto_options_menu' );
  // Prints out all settings sections added to a particular settings page.
  do_settings_sections('contexto_options_menu');
  // output save settings button
  submit_button( 'Save Settings' );
  ?>
  </form>
</div>

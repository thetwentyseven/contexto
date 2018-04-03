<?php
// TODO: Separate the file in different functions
// TODO:We must put the require in settings.php
require_once('TextRazor.php');


add_action('wp_ajax_contexto_loadingCall', 'contexto_loadingCall');
function contexto_loadingCall(){
  $highlight = $_GET['highlight'];
  echo '<h1>Loading</h1>'; // <- this should be displayed in the TB
  echo '<p>Searching <strong>'.$highlight.'</strong></p>';
  die();
}


add_action('wp_ajax_contexto_nofoundCall', 'contexto_nofoundCall');
function contexto_nofoundCall(){
  $highlight = $_GET['highlight'];
  echo '<h1>Sorry</h1>'; // <- this should be displayed in the TB
  echo '<p>The word <strong>'.$highlight.'</strong> was not found.</p>';
  echo 'Please if the word is in Wikidata and the word is not found, contact us.';
  die();
}


// Handler function - More info: https://codex.wordpress.org/Plugin_API/Action_Reference/wp_ajax_(action)
add_action( 'wp_ajax_contexto_send_text', 'contexto_send_text' );
function contexto_send_text() {
  global $wpdb;


  // Get data from the database - More info: https://developer.wordpress.org/reference/functions/get_option/
  $userdata = get_option('contexto_options_data');
  $userdata_apikey = $userdata['contexto_options_apikey'];
  $userdata_confidence = $userdata['contexto_options_confidence'];

  // Insert API key
  TextRazorSettings::setApiKey($userdata_apikey);

  // Testing account before a call
  $accountManager = new AccountManager();
  // print_r($accountManager->getAccount());

  // New instance of TextRazor
  $textrazor = new TextRazor();

  // Add an extractor
  $textrazor->addExtractor('entities');

  // Get the content from the post or page edit via AJAX - POST
  $tinymce_before = $_POST['content'];

  // Get highlight text by user
  $highlight = $_POST['highlight'];

  // Get the images folder URL via AJAX - POST
  $images_folder = $_POST['images_folder'];

  // Get the confident level from the user via AJAX - POST
  // The confidence that TextRazor is correct that this is a valid entity. TextRazor uses an ever increasing number of signals to help spot valid entities, all of which contribute to this score. These include the semantic agreement between the context in the source text and our knowledgebase, compatibility between other entities in the text, compatibility between the expected entity type and context, prior probabilities of having seen this entity across wikipedia and other web datasets. The score ranges from 0.5 to 10, with 10 representing the highest confidence that this is a valid entity - More info: https://www.textrazor.com/docs/php
  $confidence = $userdata_confidence;

  // We do not want to search words that already have a link <a href=''></a> or , <span class=''></span>
  // More info: http://php.net/manual/en/function.strip-tags.php Author: mariusz.tarnaski@wp.pl
  // We get a copy of the content to extract all the html tags and content inside them
  $text = $highlight;
  $invert = TRUE;
  // Without placing any tag, it replace all of them
  $tags =  '<span><a>';

  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
  $tags = array_unique($tags[1]);

  if(is_array($tags) AND count($tags) > 0) {
    if($invert == FALSE) {
      $text =  preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
    }
    else {
      $text =  preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
    }
  }
  elseif($invert == FALSE) {
    $text = preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
  }
  //--------------------------------------------

  // Analyze and extract the entities from the text which we extract all the html tags and content inside them
  $response = $textrazor->analyze($text);

  // Checking if the $response has entities
  if (isset($response['response']['entities'])) {
      // For each entity give me all the data
      foreach ($response['response']['entities'] as $entity) {
        // If the entity has the minimum confidence stablished by user
        if ($entity['confidenceScore'] >= $confidence) {

          // Call the Wikidata API to get the description and the image if it has one - More info: https://www.mediawiki.org/wiki/Wikibase/API#wbgetentities
          $wikidataid = $entity['wikidataId'];
          $request = wp_remote_get( "https://www.wikidata.org/w/api.php?action=wbgetentities&ids={$wikidataid}&languages=en&format=json" );

          // If the request has not an error
          if( !is_wp_error( $request ) ) {
            // Get simply the body of the call
            $body = wp_remote_retrieve_body( $request );
            // Transform it in json code or object
            $data = json_decode( $body );
            // Get only the description of the wikidata entity
            $description = $data->entities->$wikidataid->descriptions->en->value;
            // Make a string's first character uppercase
            $description = ucfirst($description);
            // Get only the image name of the wikidata entity
            $imagename = $data->entities->$wikidataid->claims->P18[0]->mainsnak->datavalue->value;
            // Convert spaces in string into +
            $imagename = str_replace(' ', '+', $imagename);
            // Call the Wikidata API: Imageinfo - More info: https://www.mediawiki.org/wiki/API:Imageinfo
            $request_image = wp_remote_get( "https://commons.wikimedia.org/w/api.php?action=query&titles=File%3A{$imagename}&prop=imageinfo&iiprop=url&iiurlheight=170&format=json" );

            // If there is no image, use the predetermined
            if( !is_wp_error( $request_image ) ) {
              // Get simply the body of the call
              $body = wp_remote_retrieve_body( $request_image );
              // Transform it in json code or object
              $data = json_decode( $body );
              // Get image url
              // More info: https://stackoverflow.com/questions/49119476/getting-first-object-from-a-json-file/49120012#49120012
              // Because we do not know the 'pageid' element, we get the first element of 'pages'
              $props = array_values(get_object_vars($data->query->pages));
              $imageurl = $props[0]->imageinfo[0]->thumburl;
            }

            // If the image is not available the user a default one
            if (empty($imageurl)) {
              // $imageurl = plugins_url( '/public/images/image-not-available.jpg', __FILE__ );
              $imageurl = "{$images_folder}image-not-available.jpg";
            }

            // Create an id with the matchedText removing all white spaces
            $classText = str_replace(' ', '', $entity['matchedText']);

            // Replace the actual text from TinyMCE with those words found with TextRazor and make a link
            $tinymce_after = str_replace($entity['matchedText'], "<span class='wpContextoToolTip tooltip-effect-1' class='{$classText}'><span class='wpContextoToolTipItem'>{$entity['matchedText']}</span><span class='wpContextoToolTipContent clearfix'><span class='wpContextoToolTipImage'><img src='{$imageurl}' class='wpContextoImages'></span><span class='wpContextoToolTipItemText'>{$description}<span class='wpContextoToolTipItemFooter'><span class='wpContextoToolTipItemSource'>Source: <a target='_blank' href='https://www.wikidata.org/wiki/{$wikidataid}'>Wikidata</a></span> <span class='wpContextoToolTipItemConfidence'>Confidence: {$entity['confidenceScore']}</span></span></span></span></span>", $tinymce_before);
          } // if $request

        } // if $entity['confidenceScore']
      } // foreach
  } // if $response['response']['entities']

  // Return the text clean, Un-quotes a quoted string - More info http://php.net/manual/en/function.stripslashes.php
  // Checking size of the string, if it is too big fires 500 error. TODO
  // print(strlen($tinymce_after));

  // If the we have not found any entity, we push back the original text
  if (isset($tinymce_after)) {
    print(stripslashes($tinymce_after));
  } else {
    print(stripslashes($tinymce_before));
  }

	wp_die();
}

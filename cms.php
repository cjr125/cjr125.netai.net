<!DOCTYPE html>
<html>
<head>

<?php require 'db.php';
      require 'HtmlUtil.php';

      if (isset($_POST["submit"])) {
          setcookie('user',$_POST['user']);
          setcookie('passwd',sha1($_POST['passwd']));
      }
      $user = $_COOKIE["user"];

      $rand = "";
      if ($_POST["hf_rand"] && $_POST["hf_rand"] != "") {
          $rand = $_POST["hf_rand"];
      }

      $error = "";
      $content = "";
      $contentType = "";
      $description = "";
      $location = "";
      $rotation = updateRotation("30");
      $width = 0;
      $height = 0;
      $position = 0;
      $rotationCapacity = 10;
      $tag_types = "";
      $keywordCategory = "";
      $keywordRelevance = array();
      $avgKeywordRelevance = 0.0;
      $maxKeywordRelevance = 0.0;
      $relevantKeywordIndex = -1;
      $keywords = "";
      $user_agent_string = $_SERVER['HTTP_USER_AGENT'];
      $ua_string_filters = "";
      $city_filters = "";
      $selected_city_filters = "";
      $stateFilters = "";
      $selected_state_filters = "";
      $countryFilters = "";
      $selected_country_filters = "";
      $url = "";
      $impression_count = 0;
      $cost_per_click = costPerClick(0);
      $selected_content_spot_id = -1;
      $content_spots = "";
      $content_spot_urls = "";
      $content_spot_categories = "";
      $selected_content_spot = "";
      $selected_content_spot_category = "";
      $selected_content_spot_url = "";
      $relevance = array();
      $startDate = new DateTime('NOW');
      $endDate = new DateTime('NOW');
      $eventTypes = "";
      $id = -1;
      if ($_COOKIE["mediaId"] && $_COOKIE["mediaId"] != "") {
          $id = intval($_COOKIE["mediaId"]);
          if ($id > -1) {
              DBLogin("a8823305_audio");
              $result = DBQuery("SELECT * FROM media WHERE id = ".$id);
              if ($result->num_rows > 0) {
                  $result = $result->fetch_row();
                  $content = str_replace("'","\'",stripslashes(htmlentities($result["content"])));
                  $contentType = $result["contentType"];
                  $description = $result["description"];
                  $location = $result["location"];
                  $width = (is_null($result["width"]) ? 0 : $result["width"]);
                  $height = (is_null($result["height"]) ? 0 : $result["height"]);
                  $tag_types = (is_null($result["tag_types"]) ? "" : $result["tag_types"]);
                  $keywordCategory = (is_null($result["keywordCategory"]) ? "Category" : $result["keywordCategory"]);
                  $result2 = DBQuery("SELECT relevance FROM relevance WHERE id = ".$id);
                  $keywordRelevance = (is_null($result2["keywordRelevance"]) ? array() : array($result2["keywordRelevance"]));
                  foreach ($keywordRelevance as $relevance) {
                      $avgKeywordRelevance += $relevance;
                      if ($relevance> $maxKeywordRelevance) {
                          $maxKeywordRelevance = $relevance;
                      }
                  }
                  $avgKeywordRelevance = $avgKeywordRelevance / $keywordRelevance->length;
                  $maxPosition = DBQuery("SELECT SUM(mediaId) FROM content_spots WHERE content_spot_id = ".$selected_content_spot_id);
                  $relevantKeywordIndex = getOptimalKeywordIndex($id, $maxKeywordRelevance, $maxPosition);
                  if ($relevantKeywordIndex != NULL) {
                      $position = $relevantKeywordIndex;
                  } else {
                      $position = (is_null($result["position"]) ? $maxPosition : $result["position"]);
                  }
                  $keywords = (is_null($result["keywords"]) ? "" : $result["keywords"]);
                  $keywords = createDropdown(",",$keywords,"dd_keywords","");
                  $ua_string_filters = (is_null($result["ua_string_filters"]) ? implode(",",explode(" ",$user_agent_string)) : $result["ua_string_filters"]);
                  $selected_city_filters = (is_null($result["city_filters"]) ? $city_filters : $result["city_filters"]);
                  $selected_state_filters = (is_null($result["stateFilters"]) ? $selected_state_filters : $result["stateFilters"]);
                  $selected_country_filters = (is_null($result["countryFilters"]) ? $selected_country_filters : $result["countryFilters"]);
                  $url = (is_null($result["url"]) ? "" : $result["url"]);
                  $impression_count = getImpressionCountByMediaId($id);
                  $cost_per_click = (is_null($result["cost_per_click"]) ? $cost_per_click : costPerClick($result["cost_per_click"]));
                  $startDate = (is_null($result["startDate"]) ? $startDate : $result["startDate"]);
                  $endDate = (is_null($result["endDate"]) ? $endDate : $result["endDate"]);
              }
              $result = DBQuery("SELECT eventType FROM impressions WHERE id = ".$id);
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $eventTypes = ($eventTypes == "" ? $row["eventType"] : ",".$row["eventType"]);
              }
              $result = DBQuery("SELECT content_spot_name,content_spot_url,content_spot_category FROM content_spots WHERE mediaId = ".$id);
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $content_spots = ($content_spots == "" ? $row["content_spot_name"] : ",".$row["content_spot_name"]);
                  $content_spot_urls = ($content_spot_urls == "" ? $row["content_spot_url"] : ",".$row["content_spot_url"]);
                  $content_spot_categories = ($content_spot_categories == "" ? $row["content_spot_category"] : ",".$row["content_spot_category"]);
              }
              DBC();
          }
      }
      if ($_POST["hf_selected_content_spot_id"] && $_POST["hf_selected_content_spot_id"] != "" && $id > -1) {
          $selected_content_spot_id = $_POST["hf_selected_content_spot_id"];
          if ($selected_content_spot_id > -1) {
              setcookie('selected_content_spot_id', $selected_content_spot_id);
              DBLogin("a8823305_audio");
              DBQuery("UPDATE content_spots SET position = ".$selected_content_spot_id." WHERE content_spot_id = ".$selected_content_spot_id.";INSERT INTO content_spots (content_spot_id,mediaId,content_spot_name,content_spot_url,content_spot_category,width,height,position,rotation,rotationCapacity,active) VALUES (SELECT * FROM content_spots WHERE content_spot_id = ".$selected_content_spot_id." ORDER BY position ASC)");
              DBC();
          }
      }
      $tb_content_spot_name = "";
      if ($_POST["hf_content_spot_name"] && $_POST["hf_content_spot_name"] != "" && $id > -1) {
          $tb_content_spot_name = $_POST["hf_content_spot_name"];
          if ($tb_content_spot_name != "Add Content Spot Name") {
              DBLogin("a8823305_audio");
              DBQuery("IF EXISTS (SELECT content_spot_id FROM content_spots WHERE mediaId = ".$id." AND content_spot_name = '".$tb_content_spot_name."')
                         UPDATE content_spots SET content_spot_name = '".$tb_content_spot_name."' WHERE content_spot_id = content_spot_id);
                       ELSE INSERT INTO content_spots (mediaId,content_spot_name,content_spot_url,content_spot_category) VALUES
                       (".$id.",'".$tb_content_spot_name."','','')");
              DBC();
          }
          $content_spots .= ($content_spots == "" ? $tb_content_spot_name : ",".$tb_content_spot_name);
      }
      $content_spots = createDropdown(",",$content_spots,"dd_content_spots",$selected_content_spot_id);
      $tb_content_spot_url = "";
      if ($_POST["hf_content_spot_url"] && $_POST["hf_content_spot_url"] != "" && $id > -1) {
          $tb_content_spot_url = $_POST["hf_content_spot_url"];
          if ($tb_content_spot_url != "Add Content Spot URL") {
              DBLogin("a8823305_audio");
              DBQuery("IF EXISTS (SELECT content_spot_id FROM content_spots WHERE mediaId = ".$id." AND content_spot_url = '".$tb_content_spot_url."')
                         UPDATE content_spots SET content_spot_url = '".$tb_content_spot_url."' WHERE content_spot_id = content_spot_id);
                       ELSE INSERT INTO content_spots (mediaId,content_spot_name,content_spot_url,content_spot_category) VALUES
                       (".$id.",'','".$tb_content_spot_url."','')");
              DBC();
          }
          $content_spot_urls .= ($content_spot_urls == "" ? $tb_content_spot_url : ",".$tb_content_spot_url);
      }
      $tb_content_spot_category = "";
      if ($_POST["hf_content_spot_category"] && $_POST["hf_content_spot_category"] != "" && $id > -1) {
          $tb_content_spot_category = $_POST["hf_content_spot_category"];
          if ($tb_content_spot_category != "Add Content Spot Category") {
              DBLogin("a8823305_audio");
              DBQuery("IF EXISTS (SELECT content_spot_id FROM content_spots WHERE mediaId = ".$id." AND content_spot_category = '".$tb_content_spot_category."')
                         UPDATE content_spots SET content_spot_category = '".$tb_content_spot_category."' WHERE content_spot_id = content_spot_id);
                       ELSE INSERT INTO content_spots (mediaId,content_spot_name,content_spot_url,content_spot_category) VALUES
                       (".$id.",'','','".$tb_content_spot_category."')");
              DBC();
          }
          $content_spot_categories .= ($content_spot_categories == "" ? $tb_content_spot_category : ",".$tb_content_spot_category);
      }
      $tb_location = "";
      if ($_POST["hf_location"] && $_POST["hf_location"] != "" && $id > -1) {
          $tb_location = $_POST["hf_location"];
          if ($tb_location != "Add Location Name") {
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media SET location = '".$tb_location."' WHERE id = ".$id);
              DBC();
          }
      }
      if ($_POST["hf_city_filters"] && $_POST["hf_city_filters"] != "" && $id > -1) {
          $selected_city_filters = $_POST["hf_city_filters"];
          DBLogin("a8823305_audio");
          DBQuery("UPDATE media SET city_filters = '".$selected_city_filters."' WHERE id = ".$id);
          DBC();
      }
      $city_filters = createDropdown(",",$city_filters,"dd_city_filters",$selected_city_filters);
      if ($_POST["hf_state_filters"] && $_POST["hf_state_filters"] != "" && $id > -1) {
          $selected_state_filters = $_POST["hf_state_filters"];
          DBLogin("a8823305_audio");
          DBQuery("UPDATE media SET stateFilters = '".$selected_state_filters."' WHERE id = ".$id);
          DBC();
      }
      $statesJSON = json_decode(file_get_contents("states_titlecase.json"));
      foreach ($statesJSON as $stateJSON) {
          $stateFilters .= ($stateFilters == "" ? $stateJSON->name : ",".$stateJSON->name);
      }
      $stateFilters = createDropdown(",",$stateFilters,"dd_state_filters",$selected_state_filters);
      if ($_POST["hf_country_filters"] && $_POST["hf_country_filters"] != "" && $id > -1) {
          $selected_country_filters = $_POST["hf_country_filters"];
          DBLogin("a8823305_audio");
          DBQuery("UPDATE media SET countryFilters = '".$selected_country_filters."' WHERE id = ".$id);
          DBC();
      }
      $countriesJSON = json_decode(file_get_contents("http://api.geonames.org/countryInfoJSON?username=demo"));
      foreach ($countriesJSON->geonames as countryJSON) {
          $countryFilters .= ($countryFilters == "" ? $countryJSON->countryName : ",".$countryJSON->countryName);
      }
      $countryFilters = createDropdown(",",$countryFilters,"dd_country_filters",$selected_country_filters);
      if ($_POST["hf_start_date"] && $_POST["hf_start_date"] != "" && $id > -1) {
          $startDate = date_create($_POST["hf_start_date"]);
          $interval = $endDate->diff($startDate);
          if ($interval->invert == 0) {
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media SET startDate = '".$startDate->format('m/d/Y H:i:s')."' WHERE id = ".$id);
              DBC();
          } else {
              $error = "Start Date must be earlier than End Date.";
          }
      }
      if ($_POST["hf_end_date"] && $_POST["hf_end_date"] != "" && $id > -1) {
          $endDate = date_create($_POST["hf_end_date"]);
          $interval = $endDate->diff($startDate);
          if ($interval->invert == 0) {
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media SET endDate = '".$endDate->format('m/d/Y H:i:s')."' WHERE id = ".$id);
              DBC();
          } else {
              $error = "Start Date must be earlier than End Date.";
          }
      }
      $tb_event_type = "";
      if ($_POST["hf_event_type"] && $_POST["hf_event_type"] != "") {
          $tb_event_type = $_POST["hf_event_type"];
          if ($tb_event_type != "Add Event Type" && strpos($eventTypes, $tb_event_type) === false && $id > -1) {
              $eventTypes .= ($eventTypes == "" ? $tb_event_type : ",".$tb_event_type);
              DBLogin("a8823305_audio");
              $eventTypes_array = explode(",",$eventTypes);
              foreach ($eventTypes_array as $eventType) {
                  $SQL = "IF EXISTS (SELECT eventType FROM impressions WHERE id = ".$id." AND eventType = ".$eventType.") 
                            UPDATE impressions SET eventType = ".$eventType." WHERE id = ".$id."   
                          ELSE INSERT INTO impressions (id,eventType,impressions) VALUES (".$id.",'".eventType."',0)";
                  DBQuery($SQL);
              }
              DBC();
          }
      }
      $eventTypes = (is_null($eventTypes) ? "" : createDropdown(",",$eventTypes,"dd_event_types",""));
      if ($_POST["hf_cost_per_click"] && $_POST["hf_cost_per_click"] != "") {
          $cost_per_click = $_POST["hf_cost_per_click"];
          if ($cost_per_click != "$0.00" && $id > -1) {
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media SET cost_per_click = '".$cost_per_click."' WHERE id = ".$id);
              DBC();
          }
      }
      if ($_POST["hf_keyword_category"] && $_POST["hf_keyword_category"] != "") {
          $keywordCategory = $_POST["hf_keyword_category"];
          if ($keywordCategory != "Category" && $id > -1) {
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media set keywordCategory = '".$keywordCategory."' WHERE id = ".$id);
              DBC();
          }
      }
      if ($_POST["hf_keyword_relevance"] && $_POST["hf_keyword_relevance"] != "") {
          $relevance = floatval($_POST["hf_keyword_relevance"]);
          $keywordRelevance[] = $relevance;
          if ($relevance == 1.0) {
              $avgKeywordRelevance += $relevance / $keywordRelevance->length;
              if ($avgKeywordRelevance > $maxKeywordRelevance) {
                  $maxKeywordRelevance = $avgKeywordRelevance;
              }
          }
      }
      $tb_city_filter = "";
      if ($_POST["hf_city_filter"] && $_POST["hf_city_filter"] != "") {
          $tb_city_filter = $_POST["hf_city_filter"];
          if ($tb_city_filter != "Add a City Filter" && strpos($city_filters, $tb_city_filter) === false && $id > -1) {
              $city_filters .= ($city_filters == "" ? $tb_city_filter : ",".$tb_city_filter);
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media set city_filters = '".$city_filters."' WHERE id = ".$id);
              DBC();
          }
      }
      $tb_ua_string_filter = "";
      if ($_POST["hf_ua_string_filter"] && $_POST["hf_ua_string_filter"] != "") {
          $tb_ua_string_filter = $_POST["hf_ua_string_filter"];
          if ($tb_ua_string_filter != "Add UA String Filter" && strpos($ua_string_filters, $tb_ua_string_filter) === false && $id > -1) {
              $ua_string_filters .= ($ua_string_filters == "" ? $tb_ua_string_filter : ",".$tb_ua_string_filter);
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media SET ua_string_filters = '".$ua_string_filters."' WHERE id = ".$id);
              DBC();
          }
      }
      $ua_string_filters = (is_null($ua_string_filters) ? "" : createDropdown(",",$ua_string_filters,"dd_ua_string_filters",""));
      $tb_tag_type = "";
      if ($_POST["hf_add_tag_type"] && $_POST["hf_add_tag_type"] != "") {
          $tb_tag_type = $_POST["hf_add_tag_type"];
          if ($tb_tag_type != "Add Tag Type" && strpos($tag_types, $tb_tag_type) === false && $id > -1) {
              $tag_types .= ($tag_types == "" ? $tb_tag_type : ",".$tb_tag_type);
              DBLogin("a8823305_audio");
              DBQuery("UPDATE media SET tag_types = '".$tag_types."' WHERE id = ".$id);
              DBC();
          }
      }
      $tb_keyword = "";
      if ($_POST["hf_add_keyword"] != "") {
          $tb_keyword = $_POST["hf_add_keyword"];
      }
      $selected_ua_string_filters = "";
      if ($_COOKIE["selected_ua_string_filters"] && $_COOKIE["selected_ua_string_filters"] != "") {
          $selected_ua_string_filters = html_entity_decode($_COOKIE["selected_ua_string_filters"]);
      }
      if ($_COOKIE["selected_content_spot_id"] && $_COOKIE["selected_content_spot_id"] != "") {
          $selected_content_spot_id = $_COOKIE["selected_content_spot_id"]);
          DBLogin("a8823305_audio");
          $result =DBQuery("SELECT content_spot_name,content_spot_url,content_spot_category FROM content_spots WHERE content_spot_id = ".$selected_content_spot_id);
          if ($result->num_rows > 0) {
              $row = $result->fetch_row();
              $selected_content_spot_name = $row["content_spot_name"];
              $selected_content_spot_url = $row["content_spot_url"];
              $selected_content_spot_category = $row["content_spot_category"];
          }
          DBC();
      }
      $selected_keywords = "";
      if ($_COOKIE["selected_keywords"] && $_COOKIE["selected_keywords"] != "") {
          $selected_keywords = html_entity_decode($_COOKIE["selected_keywords"]);
      }
      if ($_POST["contentType"] && $_POST["contentType"] != "") {
          $contentType = $_POST["contentType"];
          if ($_POST["content"] && $_POST["content"] != "") {
              if ($contentType == "html") {
                  $content = str_replace("'","\'",stripslashes(htmlentities($_POST["content"])));
              } else if ($contentType == "xml") {
                  $content = new SimpleXMLElement($content);
              }
              if ($_POST["url"] && $_POST["url"] != "") {
                  $content = parseUrl($_POST["url"]);
                  $contentType = "html";
                  $description = getDescription($_POST["url"]);
                  $filter = $_POST["hf_add_keyword"];
                  $keywords = getContentKeywordsFromUrl($url);
                  if ($_POST["hf_filter"] && $_POST["hf_filter"] != "") {
                      $keywords = createDropdown(",",$keywords,"dd_keywords",$_POST["hf_filter"]);
                  } else {
                      $keywords = createDropdown(",",$keywords,"dd_keywords","");
                  }
                  $ua_string_filters = getUAStringFilters();
                  if ($_POST["hf_ua_string_filter"] && $_POST["hf_ua_string_filter"] != "") {
                      $ua_string_filters = createDropdown(",",$ua_string_filters,"dd_ua_string_filters",$_POST["hf_ua_string_filter"]);
                  } else {
                      $ua_string_filters = createDropdown(",",$ua_string_filters,"dd_ua_string_filters","");
                  }
              }
          }
      }
      $tb_description = "";
      if ($_POST["hf_description"] && $_POST["hf_description"] != "" && $id > -1) {
          $tb_description = $_POST["description"];
          DBLogin("a8823305_audio");
          DBQuery("UPDATE media SET description = '".($tb_description == "" ? $description : $tb_description)."' WHERE id = ".$id);
          DBC();
      }
      if ($_POST["width"] && $_POST["width"] != 0) {
          $width = intval(trim($_POST["width"]));
      }
      if ($_POST["height"] && $_POST["height"] != 0) {
          $height = intval(trim($_POST["height"]));
      }
      $filter_by_keyword = false;
      if ($_POST["hf_keywords"] == "keywords" && $id > -1) {
          DBLogin("a8823305_audio");
          preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $content, $content_urls);
          $content_keywords = "";
          foreach ($content_urls[1] as $content_url) {
              $tags = get_meta_tags($content_url[0]);
              foreach ($tags["keywords"] as $keyword) {
                  $content_keywords .= ($content_keywords == "" ? $keyword : ",".$keyword);
              }
          }
          if ($content_keywords != "") {
              DBQuery("UPDATE media SET keywords = '".$content_keywords."' WHERE id = ".$id);
              $keywords = createDropdown(",",$content_keywords,"dd_keywords",$selected_keywords);
          }
          DBC();
      }
      else if ($_POST["hf_update_keywords"] == "update_keywords" && $id > -1) {
          DBLogin("a8823305_audio");
          if ($tb_keyword != "" && $tb_keyword != "Add Keyword") {
              $selected_keywords .= ($selected_keywords == "" ? $tb_keyword : ",".$tb_keyword);
              DBQuery("UPDATE media SET keywords = '".$selected_keywords."' WHERE id = ".$id);
          }
          $selected_keywords_array = explode(",",$selected_keywords);
          $selection = "";
          foreach ($selected_keywords_array as $keyword) {
              if ($keyword != "" && strpos($content,$keyword) != -1) {
                  $selection .= ($selection == "" ? $keyword : ",".$keyword);
              }
          }
          if ($selection != "") {
              DBQuery("UPDATE media SET keywords = '".$selection."' WHERE id = ".$id);
          }
          $result = DBQuery("SELECT keywords FROM media WHERE id = ".$id);
          if ($result->num_rows > 0) {
              $result = $result->fetch_row();
              $keywords = $result["keywords"];
          }
          if ($keywords && $keywords != "") {
              $keywords = createDropdown(",",$keywords,"dd_keywords",$selected_keywords);
              setcookie("selected_keywords",$selected_keywords);
          }
          DBC();
      }
      else if ($_POST["hf_update"] == "update" && $id > -1) {
          DBLogin("a8823305_audio");
          DBQuery("UPDATE media SET content='".$content."',contentType='".$contentType."',width='".$width."',height='".$height."',tag_types='".$tag_types."',keywords='".$keywords."',keywordCategory='".$keywordCategory."',ua_string_filters='".$ua_string_filters."',url='".$url."',cost_per_click='".$cost_per_click."',startDate='".$startDate->format('m/d/Y H:i:s')."',endDate='".$endDate->format('m/d/Y H:i:s')."' WHERE id = ".$id);
          DBC();
      }
      else if ($_POST["hf_delete"] == "delete" && $id > -1) {
          DBLogin("a8823305_audio");
          DBQuery("DELETE FROM media WHERE id = ".$id);
          DBC();
          setcookie('mediaId',-1);
          header("Location: cms.php");
      }
      else if ($_POST["hf_save"] == "save") {
          DBLogin("a8823305_audio");
          $content = str_replace("[timestamp]","".time(),$content);
          $result = DBQuery("INSERT INTO media (content,contentType,width,height,tag_types,keywords,keywordCategory,ua_string_filters,url,cost_per_click,startDate,endDate) VALUES ('".$content."','".$contentType."','".$width."','".$height."','".$tag_types."','".$keywords."','".$keywordCategory."','".$ua_string_filters."','".$url."','".$cost_per_click."','".$startDate->format('m/d/Y H:i:s')."','".$endDate->format('m/d/Y H:i:s')."')");
          if ($result) {
              setcookie('mediaId',$result->insert_id());
          } else {
              $error = "Unable to save media.";
          }
          DBC(); 
      }
      else if ($_POST["cb_filter"]) {
          $filter_by_keyword = $_POST["cb_filter"];
      }
      else if ($_POST["hf_filter"] == "filter") {
          DBLogin("a8823305_audio");
          $filter = $_POST["hf_add_tag_types"];
          if ($filter != "") {
              $tag_types = filterKeywords($tag_types, $filter);
              DBQuery("UPDATE media SET tag_types = '".$tag_types."' WHERE id = ".$id);
              if ($url != "") {
                  $content = getTagTypesFromUrl($url, $tag_types);
              }
          }
          $filter = $_POST["hf_add_keyword"];
          if ($filter != "") {
              $selected_keywords = filterKeywords($selected_keywords, $filter);
              $keywords = filterKeywords($keywords, $filter);
              DBQuery("UPDATE media SET keywords = '".$keywords."' WHERE id = ".$id);
              setcookie('selected_keywords',implode(",",$selected_keywords));
          }
          DBC();
      }
      else if ($_POST["hf_clear"] == "clear") {
          $content = "";
          $contentType = "";
          $width = 0;
          $height = 0;
          $tag_types = "";
          $url = "";
          setcookie('mediaId',-1);
          header("Location: cms.php");
      }
      function costPerClick($price) {
          setlocale(LC_MONETARY, 'en_US');
          try {
              $price = money_format('%i',$price);
              $price = "$".substr($price, strpos($price," ")+1);
          } catch (Exception $e) {
              $error = "Exception: ".$e->getMessage();
          }
          return $price;
      }
      function getDefaultMedia() {
          DBLogin("a8823305_audio");
          $result = DBQuery("SELECT * FROM media ORDER BY id ASC LIMIT 1");
          if ($result->num_rows > 0) {
              $media = $result->fetch_row();
              DBC();
              return $media;
          }
          DBC();
          return NULL;
      }
      function getMediaByKeywordSearch($keywords) {
          DBLogin("a8823305_audio");
          $result = DBQuery("SELECT mediaId,content_spot_url FROM content_spots WHERE active = 1");
          if ($result->num_rows > 0) {
              $media = array();
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $mediaId = $row["mediaId"];
                  $content_spot_url = $row["content_spot_url"];
                  $tags = get_meta_tags($content_spot_url);
                  foreach ($tags["keywords"] as $keyword) {
                      if (strpos($keywords, $keyword) != -1) {
                          $result2 = DBQuery("SELECT * FROM media WHERE id = ".$mediaId);
                          if ($result2->num_rows > 0) {
                              $media[] = $result2->fetch_row();
                              break;
                          }
                      }
                  }
              }
              DBC();
              return $media;
          }
          DBC();
          return NULL;
      }
      function getMediaOriginByContentSpotId($content_spot_id) {
          $DBLogin("a8823305");
          $result = DBQuery("SELECT mediaId,width,height FROM content_spots WHERE content_spot_id = ".$content_spot_id." AND active = 1");
          if ($result->num_rows() > 0) {
              $origin = array();
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $mediaId = $row["mediaId"]
                  $content_spot_width = $row["width"];
                  $content_spot_height = $row["height"];
                  $result2 = DBQuery("SELECT * FROM media WHERE id = ".$mediaId." AND width <= ".$content_spot_width." AND height <= ".$content_spot_height);
                  if ($result2->num_rows() > 0) {
                      while ($row2 = $result2->fetch_array(MYQSL_ASSOC)) {
                          $width = $row["width"];
                          $height = $row["height"];
                          $origin[] = $content_spot_width - $width / 2;
                          $origin[] = $content_spot_height - $height / 2;
                      }
                  }
              }
              DBC();
              return $origin;
          }
          DBC();
          return NULL;
      }
      function getMediaByContentSpotSize($width, $height) {
          DBLogin("a8823305");
          $result = DBQuery("SELECT mediaId,width,height FROM content_spots WHERE active = 1");
          if ($result->num_rows() > 0) {
              $media = array();
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $mediaId = $row["mediaId"];
                  $width = $row["width"];
                  $height = $row["height"];
                  $result2 = DBQuery("SELECT * FROM media WHERE id = ".$mediaId." AND width <= ".$width." AND height <= ".$height);
                  if ($result2->num_rows > 0) {
                      $media[] = $result2->fetch_row();
                  }
              }
              DBC();
              return $media;
          }
          DBC();
          return NULL;
      }
      function getMediaByContentSpotUrl($content_spot_url) {
          DBLogin("a8823305");
          $result = DBQuery("SELECT id,content,contentType,description,location,width,height,tag_types,keywords,keywordCategory,ua_string_filters,url,cost_per_click,startDate,endDate FROM media JOIN content_spots ON media.id = content_spots.mediaId WHERE content_spots.content_spot_url = ".$content_spot_url." AND content_spots.active = 1");
          if ($result->num_rows > 0) {
              $media = array();
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $media[] = $row;
              }
              DBC();
              return $media;
          }
          DBC();
          return NULL;
      }
      function getMediaByContentSpotId($content_spot_id) {
          DBLogin("a8823305");
          $result = DBQuery("SELECT id,content,contentType,description,location,width,height,tag_types,keywords,keywordCategory,ua_string_filters,url,cost_per_click,startDate,endDate FROM media JOIN content_spots ON media.id = content_spots.mediaId WHERE content_spots.content_spot_id = ".$content_spot_id." AND content_spots.active = 1");
          if ($result->num_rows > 0) {
              $media = array();
              while ($row = $result->fetch_array(MYSQL_ASSOC)) {
                  $media[] = $row;
              }
              DBC();
              return $media;
          }
          DBC();
          return NULL;
      }
      function getMediaByDate($startdate, $enddate) {
          DBLogin("a8823305");
          $result = DBQuery("SELECT * FROM media WHERE startDate >= ".$startdate." AND endDate <= ".$enddate);
          if ($result->num_rows > 0) {
              $result = $result->fetch_row();
              DBC();
              return $result;
          }
          DBC();
          return NULL;
      }
      function getOptimalKeywordRelevance($mediaId, $maxKeywordRelevance, $maxPosition) {
          if ($mediaId > -1) {
              DBLogin("a8823305");
              $result = DBQuery("SELECT content_spot_url,position,rotation,rotationCapacity FROM content_spots WHERE mediaId = ".$mediaId." AND active = 1");
              if ($result->num_rows > 0) {
                  $optimalPosition = 0;
                  $keywordRelevance = 0;
                  while ($row = $result->fetch_row()) {
                      $content_spot_url = (is_null($result["content_spot_url"]) ? "" : $result["content_spot_url"]);
                      $tags = get_meta_tags($content_spot_url);
                      foreach ($tags["keywords"] as $tag) {
                          if (strpos($keywords, $tag) != -1) {
                              if (++$keywordRelevance > $maxKeywordRelevance) {
                                  $maxKeywordRelevance = $keywordRelevance;
                                  $optimalPosition = (is_null($row["position"]) ? 0 : round(intval($row["position"]) / $maxPosition * $maxKeywordRelevance));
                              }
                          }
                      }
                  }
                  DBC();
                  return $optimalPosition;
              }
              DBC();
              return NULL;
          }
          return NULL;
      }
      function getKeywordRelevance($keyword) {
          if ($id > -1) {
              DBLogin("a8823305");
              $result = DBQuery("SELECT relevance FROM relevance WHERE keyword = '".$keyword."' AND mediaId = ".$id);
              if ($result->num_rows > 0) {
                  $result = $result->fetch_row();
                  $result = floatval($result["relevance"]);
                  DBC();
                  return $result;
              }
              DBC();
              return NULL;
          } else {
              return NULL;
          }
      }
      function getMaxKeywordRelevance($mediaId) {
          if ($mediaId > -1) {
              DBLogin("a8823305");
              $result = DBQuery("SELECT relevance FROM relevance WHERE mediaId = ".$mediaId);
              if ($result->num_rows > 0) {
                  $result = $result->fetch_row();
                  $result = floatval($result["relevance"]);
                  if ($result > $maxKeywordRelevance) {
                      $maxKeywordRelevance = $result;
                  }
              }
              DBC();
          }
          return $maxKeywordRelevance;
      }
      function getImpressionCountByMediaId($mediaId) {
          if ($mediaId > -1) {
              DBLogin("a8823305");
              $result = DBQuery("SELECT impressions FROM impressions WHERE mediaId = ".$mediaId);
              if ($result->num_rows > 0) {
                  $result = $result->fetch_row();
                  $result = intval($result["impressions"]);
                  $impression_count = $result;
              }
              DBC();
          }
          return $impression_count;
      }
      function filterKeywords($keywords, $filter) {
          $keywords = explode(",",$keywords);
          return array_filter($keywords, create_function('$a','return preg_match("/^$filter$/", $a);'));
      }
      function parseUrl($url) {
          return stripslashes(htmlentities(file_get_contents(urlencode($url))));
      }
      //returns a comma delimited list of meta keywords from the given url
      function getContentKeywordsFromUrl($url) {
          $content = parseUrl($url);
          preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $content, $content_urls);
          $content_keywords = "";
          foreach ($content_urls[1] as $content_url) {
              $tags = get_meta_tags($content_url[0]);
              foreach ($tags["keywords"] as $keyword) {
                  $content_keywords .= ($content_keywords == "" ? $keyword : ",".$keyword);
              }
          }
          return $content_keywords;
      }
      function getDescription($url) {
          $doc = new DOMDocument;
          $doc->loadHTMLFile($url);

          $metas = $doc->getElementsByTagName('meta');

          $description = "";
          foreach ($metas as $meta) {
              if (strtolower($meta->getAttribute('name')) == 'description') {
                  $description = $meta->getAttribute('value');
              }
          }
          return $description;
      }
      function updateKeywordRelevance($url) {
          $content = parseUrl($url);
          preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $content, $content_url);
          $i = 0;
          $avgKeywordRelevance = 0.0;
          $keywords_array = explode(",",$keywords);
          foreach ($content_urls[1] as $content_url) {
              if (strpos($content_url, $keywords[$i]) != -1) {
                  $keywordRelevance[$i] += 1.0;
              }
              $tags = get_meta_tags($content_url[0]);
              foreach ($tags["keyword"] as $keyword) {
                  if (strpos($keyword, $keywords_array[$i]) != -1) {
                      $keywordRelevance[$i] += 1.0;
                  }
              }
              $avgKeywordRelevance += $keywordRelevance[$i];
              $i++;
          }
          $avgKeywordRelevance = $avgKeywordRelevance / $i;
      }
      function updateRotation($secs) {
          $rotation = strtotime("0000-00-00 00:".$secs.":00");
          return $rotation;
      }
      //returns a comma delimited list of filterable user agent string tokens
      function getUAStringFilters($source = "") {
          if ($source == "") {
              $source = $user_agent_string;
          }
          return implode(",",explode(" ",$source));
      }    
      //returns array of tags with the given types from url
      function getTagTypesFromUrl($url, $tag_types, $filter_by_keyword = false) {
          $doc = new DOMDocument();
          $doc->loadHTMLFile($url);
          $tag_types_array = explode(",",$tag_types);
          $tag_contents_array = array();
          $return = "";
          foreach ($tag_types_array as $tag_type) {
              $nodes = $doc->getElementsByTagName($tag_type);
              $tag_contents_array[] = dnl2array($nodes);
              foreach ($tag_contents_array as $tag_content) {
                  if ($filter_by_keyword) {
                      foreach ($keywords as $keyword) {
                          if (array_key_exixts($keyword, $tag_content->textContent)) {
                              $return .= $tag_content->textContent;
                          }
                      }
                  } else {
                      $return .= $tag_content->textContent;
                  }
              }
          }
          return $return;
      }
      function dnl2array($domnodelist) {
          $return = array();
          for ($i = 0; $i < $domnodelist->length; ++$i) {
              $return[] = $domnodelist->item($i);
          }
          return $return;
      }
?>
<script type="text/javascript" src="js/jquery-1.10.2.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>
<script type="text/javascript">
  var starttime,eventId,mediaId,eventType,created;
  eventId = <?php echo $eventId; ?>;
  mediaId = <?php echo $mediaId; ?>;
  created = new Date();
  created = created.getTime();
  eventType = <?php echo $eventType; ?>;
  $(document).ready(function() {
    var d = new Date();
    starttime = d.getTime();
    $(window).unload(function() {
      d = new Date();
      endtime = d.getTime();
      $.ajax({ 
        url: "time.php",
        data: {'time': endtime - starttime,
               'eventId': eventId,
               'mediaId': mediaId,
               'eventType': eventType,
               'created': created}
      });
    });
    var cache_breaker = randomString(32, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $("#form1").attr('action', $("#form1").attr('action') + '?r=' + cache_breaker);
    $("#start_date").datepicker();
    $("#end_date").datepicker();
    $("#start_date").change(function() {
      if ($("#start_date").val().indexOf(":") == -1) {
        var date = new Date();
        var minutes = formatTime(date.getMinutes());
        var seconds = formatTime(date.getSeconds());
        $("#start_date").val($("#start_date").val() + " " + date.getHours() + ":" + minutes + ":" + seconds);
      }
    });
    $("#end_date").change(function() {
      if ($("#end_date").val().indexOf(":") == -1) {
        var date = new Date();
        var minutes = formatTime(date.getMinutes());
        var seconds = formatTiem(date.getSeconds());
        $("#end_date").val($("#end_date").val() + " " + date.getHours() + ":" + minutes + ":" + seconds);
      }
    });
    $("#dd_keywords").change(function() {
      var selected_keywords = document.cookie["selected_keywords"];
      if (selected_keywords) {
        selected_keywords += (selected_keywords != "" ? "," : "") + $("#dd_keywords:selected").val();
        $("#content").val($("#content").val().replace("/[keyword]/g", selected_keywords));
      }
      document.cookie["selected_keywords"] = selected_keywords;
    });
    $("#cb_filter").prop("checked",<?php echo ($filter_by_keyword ? "true" : "false"); ?>);
    $("#dd_ua_string_filters").change(function() {
      var selected_ua_string_filters = document.cookie["selected_ua_string_filters"];
      if (selected_ua_string_filters) {
        selected_ua_string_filters += (selected_ua_string_filters != "" ? "," : "") + $("#dd_ua_string_filters:selected").val();
      }
      document.cookie["selected_ua_string_filters"] = selected_ua_string_filters;
    });
    $("#dd_state_filters").change(function() {
      var selected_state_filters = document.cookie["selected_state_filters"];
      if (selected_state_filters) {
        selected_state_filters += (selected_state_filters != "" ? "," : "") + $("#dd_selected_state_filters:selected").val();
      }
      document.cookie["selected_state_filters"] = selected_state_filters;
      $("#hf_selected_state_filters").val(selected_state_filters);
      $("#form1").submit();
    });
    $("#dd_event_types").change(function() {
      var selected_event_types = document.cookie["selected_event_types"];
      if (selected_event_types) {
        selected_event_types += (selected_event_types != "" ? "," : "") + $("#dd_event_types:selected").val();
      }
      document.cookie["selected_event_types"] = selected_event_types;
    });
    $("#dd_content_spots").change(function() {
      var selected_content_spot_names = document.cookie["selected_content_spot_names"];
      if (selected_content_spot_names) {
        selected_content_spot_names += (selected_content_spot_names != "" ? "," : "") + $("#dd_content_spots:selected").val();
      }
      document.cookie["selected_content_spot_names"] = selected_content_spot_names;
      var selected_content_spot_id = -1;
      selected_content_spot_id = $("#dd_content_spots:selected").val();
      if (selected_content_spot_id > -1) {
        $("#hf_selected_content_spot_id") = selected_content_spot_id;
        $("#form1").submit();
      }
    });
    $("#dd_content_spot_urls").change(function() {
      var selected_content_spot_urls = document.cookie["selected_content_spot_urls"];
      if (selected_content_spot_urls) {
        selected_content_spot_urls += (selected_content_spot_urls != "" ? "," : "") + $("#dd_content_spot_urls:selected").val();
      }
      document.cookie["selected_content_spot_urls"] = selected_content_spot_urls;
    });
    $("#dd_content_spot_categories").change(function() {
      var selected_content_spot_categories = document.cookie["selected_content_spot_categories"];
      if (selected_content_spot_categories) {
        selected_content_spot_categories += (selected_content_spot_categories != "" ? "," : "") + $("#dd_content_spot_categories:selected").val();
      }
      document.cookie["selected_content_spot_categories"] = selected_content_spot_categories;
    });
    $("#tb_content_spot_name").on("click", function() {
      if ($("#tb_content_spot_name").val() == "Add Content Spot Name") {
        $("#tb_content_spot_name").val("");
      }
    });
    $("#btn_add_content_spot_name").on("click", function() {
      $("#hf_content_spot_name").val($("#tb_content_spot_name").val());
    });
    $("#tb_content_spot_url").on("click", function() {
      if ($("#tb_content_spot_url").val() == "Add Content Spot Url") {
        $("#tb_content_spot_url").val("");
      }
    });
    $("#btn_add_content_spot_url").on("click", function() {
      $("#hf_content_spot_url").val($("#tb_content_spot_url").val());
    });
    $("#tb_content_spot_category").on("click", function() {
      if ($("#tb_content_spot_category").val() == "Add Content Spot Category") {
        $("#tb_content_spot_category").val("");
      }
    });
    $("#btn_add_content_spot_category").on("click", function() {
      $("#hf_content_spot_category").val("#tb_content_spot_category").val());
    });
    $("#width").on("click", function() {
      if ($("#width").val() == "0") {
        $("#width").val("");
      }
    });
    $("#height").on("click", function() {
      if ($("#height").val() == "0") {
        $("#height").val("");
      }
    });
    $("#tb_description").on("click", function() {
      if ($("#tb_description").val() == "Enter a description") {
        $("#tb_description").val("");
      }
    });
    $("#tb_location").on("click", function() {
      if ($("tb_location").val() == "Add location name") {
        $("#tb_location").val("");
      }
    });
    $("#add_keyword_category").on("click", function() {
      if ($("#add_keyword_category").val() == "Category") {
        $("#add_keyword_category").val("");
      }
    });
    $("#add_city_filter").on("click", function() {
      if ($("#add_city_filter").val() == "Add a City Filter") {
        $("#add_city_filter").val("");
      }
    });
    $("#btn_add_city_filter").on("click", function() {
      clearHiddenFields();
      $("#hf_city_filter").val($("#add_city_filter").val().trim());
      $("#form1").submit();
    });
    $("#add_ua_string_filter").on("click", function() {
      if ($("#add_ua_string_filter").val() == "Add UA String Filter") {
        $("#add_ua_string_filter").val("");
      }
    });
    $("#btn_add_ua_string_filter").on("click", function() {
      clearHiddenFields();
      $("#hf_ua_string_filter").val($("#add_ua_string_filter").val().trim());
      $("#form1").submit();
    });
    $("#add_event_type").on("click", function() {
      if ($("#add_event_type").val() == "Add Event Type") {
        $("#add_event_type").val("");
      }
    });
    $("#btn_add_event_type").on("click", function() {
      clearHiddenFields();
      $("#hf_event_type").val($("#add_event_type").val().trim());
      $("#form1").submit();
    });
    $("#add_tag_type").on("click", function() {
      if ($("#add_tag_type").val() == "Add Tag Type") {
        $("#add_tag_type").val("");
      }
    });
    $("#btn_add_tag_type").on("click", function() {
      clearHiddenFields();
      $("#hf_add_tag_type").val($("#add_tag_type").val().trim());
      $("#form1").submit();
    });
    $("#add_keyword").on("click", function() {
      if ($("#add_keyword").val() == "Add Keyword") {
        $("#add_keyword").val("");
      }
    });
    $("#btn_add_keyword").on("click", function() {
      clearHiddenFields();
      $("#hf_update_keywords").val("update_keywords");
      $("#hf_add_keyword").val($("#add_keyword").val().trim());
      $("#form1").submit();
    });
    $("#btn_generate_keywords").on("click", function() {
      clearHiddenFields();
      $("#hf_keywords").val("keywords");
      $("#form1").submit();
    });
    $("#btn_update_keywords").on("click", function() {
      clearHiddenFields();
      $("#hf_update_keywords").val("update_keywords");
      $("#form1").submit();
    });
    $("#btn_save").on("click", function() {
      clearHiddenFields();
      $("#hf_rand").val(cache_breaker);
      $("#hf_start_date").val($("#start_date").val());
      $("#hf_end_date").val($("#end_date").val());
      $("#hf_cost_per_click").val($("#cost_per_click").val());
      $("#hf_description").val($("#tb_description").val());
      $("hf_location").val($("#tb_location").val());
      $("#hf_keyword_category").val($("#add_keyword_category").val());
      $("#hf_save").val("save");
      $("#form1").submit();
    });
    $("#btn_update").on("click", function() {
      clearHiddenFields();
      $("#hf_rand").val(cache_breaker);
      $("#hf_start_date").val($("#start_date").val());
      $("#hf_end_date").val($("#end_date").val());
      $("#hf_cost_per_click").val($("#cost_per_click").val());
      $("#hf_location").val($("#location").val());
      $("#hf_keyword_category").val($("#add_keyword_category").val());
      $("#hf_keyword_relevance").val("<?php echo $keywordRelevance[$keywordRelevance->length-1]; ?>");
      $("#hf_update").val("update");
      $("#form1").submit();
    });
    $("#btn_delete").on("click", function() {
      clearHiddenFields();
      $("#hf_delete").val("delete");
      $("#form1").submit();
    });
    $("#btn_filter").on("click", function() {
      clearHiddenFields();
      $("#hf_filter").val("filter");
      $("#hf_ua_filter").val($("#add_ua_string_filter").val().trim());
      $("#hf_add_tag_type").val($("#add_tag_type").val().trim());
      $("#hf_add_keyword").val($("#add_keyword").val().trim());
      $("#form1").submit();
    });
    $("#btn_clear").on("click", function() {
      clearHiddenFields();
      $("#hf_clear").val("clear");
      $("#form1").submit();
    });
    function clearHiddenFields() {
      $("#hf_rand").val("");
      $("#hf_start_date").val("");
      $("#hf_end_date").val("");
      $("#hf_cost_per_click").val("");
      $("#hf_selected_content_spot_id").val("");
      $("#hf_content_spot_name").val("");
      $("#hf_content_spot_url").val("");
      $("#hf_content_spot_category").val("");
      $("#hf_description").val("");
      $("#hf_location").val("");
      $("#hf_keyword_category").val("");
      $("#hf_city_filter").val("");
      $("#hf_ua_string_filter").val("");
      $("#hf_state_filters").val("");
      $("#hf_event_type").val("");
      $("#hf_add_tag_type").val("");
      $("#hf_add_keyword").val("");
      $("#hf_keywords").val("");
      $("#hf_save").val("");
      $("#hf_update").val("");
      $("#hf_delete").val("");
      $("#hf_clear").val("");
    }
    function randomString(length, chars) {
      var result = '';
      for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
      return result;
    }
    function formatTime(time) {
      return (time.length < 2 ? "0" + time : time);
    }
  });
</script>

<link rel="stylesheet" href="css/ui-darkness/jquery-ui-1.10.4.custom.min.css" />
<link rel="stylesheet" type="text/css" href="css/cms.css" />   
</head>
<body class="cms"> 
<?php
      if (logged_in()) {
          echo "Logged in as ".$user.". <a href=\"logout.php\">Logout</a><br>\n";
          include 'menu_bar.php';
          echo "<form id='form1' name='form1' action='cms.php' method='post'>\n";
          echo "<table id='add_media' border='1'><tr><th>content</th><th>contentType</th><th>keywords</th><th>url</th><th></th></tr>\n";
          echo "<tr><td><textarea id='content' name='content' rows='25' cols='60'>".str_replace("\'","'",$content)."</textarea></td>\n";
          echo "<td style='vertical-align:top;'><input type='text' id='contentType' name='contentType' value='".$contentType."' style='width:80px;' /><br>\n";
          echo "<label for='width'>Width:</label><br>\n"; 
          echo "<input type='text' id='width' name='width' value='".$width."' style='color:grey;width:80px' /><br>\n";
          echo "<label for='height'>Height:</label><br>\n";
          echo "<input type='text' id='height' name='height' value='".$height."' style='color:grey;width:80px' /></td>\n";
          echo "<td id='td_keywords' style='vertical-align:top;'><input type='text' id='add_keyword_category' name='add_keyword_category' value='".$keywordCategory."' class='editable' /><br>\n";
          if ($keywords != "") {
              echo "<label for='cb_filter'>Filter by Keywords:</label><input type='checkbox' id='cb_filter' name='cb_filter' style='vertical-align:bottom' /><br>\n";
              echo $keywords."<br>\n";
          }
          if ($city_filters != "") {
              echo "<label for='dd_city_filters'>City Filters:</label><br>\n";
              echo $city_filters."<br>\n";
          }
          if ($stateFilters != "") {
              echo "<label for='dd_state_filters'>State Filters:</label><br>\n";
              echo $stateFilters."<br>\n";
          }
          if ($ua_string_filters != "") {
              echo "<label for='dd_ua_string_filters'>UA String Filters:</label><br>\n";
              echo $ua_string_filters."<br>\n";
          }
          if ($eventTypes != "") {
              echo "<label for='dd_event_types'>Event Types:</label><br>\n";
              echo $eventTypes."<br>\n";
          }
          if ($content_spots != "") {
              echo "<label for='dd_content_spots'>Content Spots:</label><br>\n";
              echo $content_spots."<br>\n";
          }
          if ($content_spot_urls != "") {
              echo "<label for='dd_content_spot_urls'>Content Spot Urls:</label><br>\n";
              echo $content_spot_urls."<br>\n";
          }
          if ($content_spot_categories != "") {
              echo "<label for='dd_content_spot_categories'>Content Spot Categories:</label><br>\n";
              echo $content_spot_categories."<br>\n";
          }
          echo "</td>\n";
          echo "<td style='vertical-align:top;'><input type='text' id='url' name='url' value='".$url."' style='width:350px;' /><br>\n";
          echo "<label for='start_date' class='lbl_date'>Start Date:</label>&nbsp;<label for='end_date' class='lbl_date'>End Date:</label>&nbsp;<label for='cost_per_click'>Cost/Click:</label><br>\n";
          echo "<input type='text' id='start_date' name='start_date' value='".$startDate->format('m/d/Y H:i:s')."' class='tb_date' />";
          echo "<input type='text' id='end_date' name='end_date' value='".$endDate->format('m/d/Y H:i:s')."' class='tb_date' />";
          echo "<input type='text' id='cost_per_click' name='cost_per_click' value='".$cost_per_click."' style='width:65px;' /><br>\n";
          if ($error != "") {
              echo "<span class='error'>".$error."</span><br>\n";
          }
          if ($tag_types != "") {
              echo "Tag Types: <br>\n";
              $tag_types = explode(",",$tag_types);
              foreach ($tag_types as $tag_type) {
                  echo "&lt;".$tag_type."&gt;";
              }
          }
          echo "</td>\n";
          echo "<td style='vertical-align:top;'><input type='text' id='tb_content_spot_name' value='".$tb_content_spot_name."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_content_spot_name' value='Add Content Spot Name'></input><br>\n";
          echo "<input type='text' id='tb_content_spot_url' value='".$tb_content_spot_url."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_content_spot_url' value='Add Content Spot Url'></input><br>\n";
          echo "<input type='text' id='tb_content_spot_category' value='".$tb_content_spot_category."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_content_spot_category' value='Add Content Spot Category'></input><br>\n";
          echo "<input type='text' id='tb_description' name='tb_description' value='".$tb_description."' class='editable' /><br>\n";
          echo "<input type='text' id='tb_location' name='tb_location' value='".$tb_location."' class='editable' /><br>\n";
          echo "<input type='text' id='add_city_filter' name='add_city_filter' value='"
               .($tb_city_filter == "" ? "Add a City Filter" : $tb_city_filter)."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_city_filter' name='btn_add_city_filter' value='Add a City Filter'></input><br>\n";
          echo "<input type='text' id='add_ua_string_filter' name='add_ua_string_filter' value='"
               .($tb_ua_string_filter == "" ? "Add UA String Filter" : $tb_ua_string_filter)."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_ua_string_filter' name='btn_add_ua_string_filter' value='Add UA String Filter'></input><br>\n";
          echo "<input type='text' id='add_event_type' name='add_event_type' value='".($tb_event_type == "" ? "Add Event Type" : $tb_event_type)."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_event_type' name='btn_add_event_type' value='Add Event Type'></input><br>\n";
          echo "<input type='text' id='add_tag_type' name='add_tag_type' value='".($tb_tag_type == "" ? "Add Tag Type" : $tb_tag_type)."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_tag_type' name='btn_add_tag_type' value='Add Tag Type'></input><br>\n";
          echo "<input type='text' id='add_keyword' name='add_keyword' value='".($tb_keyword == "" ? "Add Keyword" : $tb_keyword)."' class='editable' /><br>\n";
          echo "<input type='button' id='btn_add_keyword' name='btn_add_keyword' value='Add Keyword'></input><br>\n";
          echo "<input type='button' id='btn_generate_keywords' name='keywords' value='Generate Keywords'></input><br>\n";
          echo "<input type='button' id='btn_save' name='save' value='Save Media'></input><br>\n";
          if ($id > -1) {
              echo "<input type='button' id='btn_update' name='update' value='Update Media'></input><br>\n";
              echo "<input type='button' id='btn_delete' name='delete' value='Delete Media'></input><br>\n";
          }
          echo "<input type='button' id='btn_filter' name='filter' value='Filter'></input>\n";
          echo "<input type='button' id='btn_clear' name='clear' value='Clear'></input>\n";
          echo "</td></tr></table>\n";
          echo "<input type='hidden' id='hf_rand' name='hf_rand' value='' />\n";
          echo "<input type='hidden' id='hf_start_date' name='hf_start_date' value='' />\n";
          echo "<input type='hidden' id='hf_description' name='hf_description' value='' />\n";
          echo "<input type='hidden' id='hf_location' name='hf_location' value='' />\n";
          echo "<input type='hidden' id='hf_end_date' name='hf_end_date' value='' />\n";
          echo "<input type='hidden' id='hf_cost_per_click' name='hf_cost_per_click' value='' />\n";
          echo "<input type='hidden' id='hf_selected_content_spot_id' name='hf_selected_content_spot_id' value='' />\n";
          echo "<input type='hidden' id='hf_content_spot_name' name='hf_content_spot_name' value='' />\n";
          echo "<input type='hidden' id='hf_content_spot_url' name='hf_content_spot_url' value='' />\n";
          echo "<input type='hidden' id='hf_content_spot_category' name='hf_content_spot_category' value='' />\n";
          echo "<input type='hidden' id='hf_keyword_category' name='hf_keyword_category' value='' />\n";
          echo "<input type='hidden' id='hf_keyword_relevance' name='hf_keyword_relevance' value='' />\n";
          echo "<input type='hidden' id='hf_city_filter' name='hf_city_filter' value='' />\n";
          echo "<input type='hidden' id='hf_ua_string_filter' name='hf_ua_string_filter' value='' />\n";
          echo "<input type='hidden' id='hf_state_filters' name='hf_state_filters' value='' />\n";
          echo "<input type='hidden' id='hf_event_type' name='hf_event_type' value='' />\n";
          echo "<input type='hidden' id='hf_add_tag_type' name='hf_add_tag_type' value='' />\n";
          echo "<input type='hidden' id='hf_add_keyword' name='hf_add_keyword' value='' />\n";
          echo "<input type='hidden' id='hf_keywords' name='hf_keywords' value='' />\n";
          echo "<input type='hidden' id='hf_update_keywords' name='hf_update_keywords' value='' />\n";
          echo "<input type='hidden' id='hf_save' name='hf_save' value='' />\n";
          echo "<input type='hidden' id='hf_update' name='hf_update' value='' />\n";
          echo "<input type='hidden' id='hf_delete' name='hf_delete' value='' />\n";
          echo "<input type='hidden' id='hf_filter' name='hf_filter' value='' />\n";
          echo "<input type='hidden' id='hf_clear' name='hf_clear' value='' />\n";
          echo "</form>\n";
          if ($id > -1) {
              echo "<iframe src='media.php?id=".$id."&rand=".$rand."' style='width:".($width == 0 ? 1194 : $width)."px;".($height == 0 ? "" : "height:"
                   .$height."px;")."'></iframe>\n";
          }
      }
      else {
          echo "<form id='cmslogin' action='cms.php' method='post'>\n";
          echo "<span>Username:</span><input id='user' type='text' name='user'><br>\n";
          echo "<span>Password:</span><input id='passwd' type='password' name='passwd'><br>\n";
          echo "<input id='submit' type='submit' name='submit'>\n";
          echo "</form>\n";
      }
?>
</body>
</html>
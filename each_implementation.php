<?
require 'vendor/autoload.php';

// Copyright 2018, Thomas Pronk
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
// http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License. 

// This function converts a JSON patch operation that has an 'each' extension
// to one or more traditional JSON patch operations. For an explanation of the
// 'each' extension, see the GitHub repo that this file comes from at
// https://github.com/tpronk/json_patch_each
function translate_each_patch_operation($patch_operation) {
  $result = array();
  $each = !property_exists($patch_operation, "each")? false: $patch_operation->each;
  if ($each && is_object($patch_operation->value)) {
    // *** If value is an object, set each of it's properties in path
    if (substr($patch_operation->path, -1) === "-") {
      throw new Exception("Invalid path for JSON each extension with object values: " . $patch_operation->path);
    }
    foreach (get_object_vars($patch_operation->value) as $key => $value) {
      // One patch operation for each element in value
      $result[] = (object) array(
        "value" => $value,
        "path" => $patch_operation->path . "/" . $key,
        "op" => $patch_operation->op
      );
    }
  } elseif ($each && is_array($patch_operation->value)) {
    // *** If value is an array, append each element to path
    $segments = explode("/", $patch_operation->path);
    $last_segment = array_pop($segments);
    $path_prefix = implode("/", $segments);
    // Check is last segment is valid for arrays; should be "-" or an integer >= 0
    if ($last_segment !== "-" && !(is_numeric($last_segment) && $last_segment >= 0 && $last_segment == round($last_segment))) {
      throw new Exception("Invalid path for JSON each extension with array values: " . $patch_operation->path);
    }    
    foreach ($patch_operation->value as $value) {
      // One patch operation for each element in value
      $result[] = (object) array(
        "value" => $value,
        "path" => $path_prefix . "/" . $last_segment,
        "op" => $patch_operation->op
      );
      // Increment last segment in path if it's a numeric index
      $last_segment = $last_segment === "-"? "-": $last_segment + 1;
    }    
  } else {
    // Value is not a object or array, or each is not true; no translation required
    $result = (object) array(
      "value" => $patch_operation->value,
      "path" => $patch_operation->path,
      "op" => $patch_operation->op
    );
  }
  return $result;
}

function translate_each_patch($patch) {
  $result = array();
  foreach ($patch as $patch_operation) {
    $extended_patch_operations = translate_each_patch_operation($patch_operation);
    if (is_array($extended_patch_operations)) {
      $result = array_merge($result, $extended_patch_operations);
    } else {
      $result[] = $extended_patch_operations;
    }
  }
  return ($result);
}

// *** Examples
// Patching an array with another array
$original_json = <<<'JSON'
["s1", "s2", "s3"]
JSON;
$extended_patch_json = <<<'JSON'
[{
  "value": ["t1", "t2"],
  "path": "/-",
  "op": "add",
  "each": true
}]
JSON;
$original = json_decode($original_json);
var_dump($original);
$extended_patch = json_decode($extended_patch_json);
var_dump($extended_patch);
$json_patch = translate_each_patch($extended_patch);
var_dump($json_patch);
$patch_object = Swaggest\JsonDiff\JsonPatch::import($json_patch);
var_dump($patch_object);
$patch_object->apply($original);
var_dump($original);

// Patching an object with another object
$original_json = <<<'JSON'
{"p1": "s1", "p2": "s2"}
JSON;
$extended_patch_json = <<<'JSON'
[{
  "value": {"q1": "t1", "q2": "t2"},
  "path": "",
  "op": "add",
  "each": true
}]
JSON;
$original = json_decode($original_json);
var_dump($original);
$extended_patch = json_decode($extended_patch_json);
var_dump($extended_patch);
$json_patch = translate_each_patch($extended_patch);
var_dump($json_patch);
$patch_object = Swaggest\JsonDiff\JsonPatch::import($json_patch);
var_dump($patch_object);
$patch_object->apply($original);
var_dump($original);
?>
<?php
/**
 * Return a List of Waste -- Called Disposal in LeafData
 */

use Edoceo\Radix\DB\SQL;

$ret_code = 203;

$obj_name = 'waste';

$age = CRE_Sync::age($obj_name);


// Load Cache Data
$sql = "SELECT guid, hash FROM {$obj_name}";
$res_cached = SQL::fetch_mix($sql);


// Load Fresh Data?
if ($age >= CRE_Sync::MAX_AGE) {

	$cre = \CRE::factory($_SESSION['cre']);

	$res_source = new CRE_Iterator_LeafData($cre->disposal());

	foreach ($res_source as $src) {

		$hash = _hash_obj($src);

		if ($hash != $res_cached[ $src['global_id'] ]) {

			$idx_update++;

			CRE_Sync::save($obj_name, $guid, $hash, $src);

		}
	}

	CRE_Sync::age($obj_name, time());

}
//echo "Count Cache: " . count($res_cached) . "\n";
//echo "Count Fresh: $idx_source\n";


// Now Fetch all from DB and Send Back
$res_output = array();
$sql = "SELECT guid, hash, meta FROM {$obj_name} ORDER BY guid DESC";
$res_source = SQL::fetch_all($sql);

foreach ($res_source as $src) {

	$out = array(
		'guid' => $src['guid'],
		'hash' => $src['hash'],
		//'code' => trim($src['external_id']),
		//'name' => sprintf('%s in %s', trim($src['strain_name']), trim($src['area_name'])),
	);

	if ($out['hash'] != $res_cached[ $out['guid'] ]) {
		$out['_updated'] = 1;
		$out['_source'] = json_decode($src['meta'], true);
	}

	$res_output[] = $out;

}


$ret_code = ($idx_update ? 200 : 203);

$RES = $RES->withHeader('x-openthc-update', $idx_update);

return $RES->withJSON(array(
	'status' => 'success',
	'result' => $res_output,
), $ret_code, JSON_PRETTY_PRINT);

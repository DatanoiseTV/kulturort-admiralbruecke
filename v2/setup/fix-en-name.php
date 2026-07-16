<?php
namespace ProcessWire;
include __DIR__ . '/../index.php';
wire('users')->setCurrentUser(wire('users')->get('djam'));
$home = wire('pages')->get('/');
$en = wire('languages')->get('en');
$home->of(false);
$home->setName('en', $en);
$home->save();
echo "home en name now: ", $home->get("name$en->id"), "\n";
echo "home en localUrl: ", $home->localUrl($en), "\n";
$ort = wire('pages')->get('/ort/');
echo "ort en localUrl: ", $ort->localUrl($en), "\n";

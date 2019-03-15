<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\oeaw\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class OeawPathProcessor implements InboundPathProcessorInterface 
{
    public function processInbound($path, Request $request) {
        if (0 === strpos($path, '/oeaw_detail/')) {
            $names = preg_replace('|^\/oeaw_detail\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/oeaw_detail/$names";
        }

        if (0 === strpos($path, '/get_collection_data/')) {
            $names = preg_replace('|^\/get_collection_data\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/get_collection_data/$names";
        }

        if (0 === strpos($path, '/oeaw_dl_collection/')) {
            $names = preg_replace('|^\/oeaw_dl_collection\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/oeaw_dl_collection/$names";
        }

        if (0 === strpos($path, '/oeaw_dlc/')) {
            $names = preg_replace('|^\/oeaw_dlc\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/oeaw_dlc/$names";
        }

        if (0 === strpos($path, '/oeaw_inverse_result/')) {
            $names = preg_replace('|^\/oeaw_inverse_result\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/oeaw_inverse_result/$names";
        }

        if (0 === strpos($path, '/api/checkIdentifier/')) {
            $names = preg_replace('|^\/api/checkIdentifier\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/api/checkIdentifier/$names";
        }

        if (0 === strpos($path, '/oeaw_ismember_result/')) {
            $names = preg_replace('|^\/oeaw_ismember_result\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/oeaw_ismember_result/$names";
        }

        if (0 === strpos($path, '/oeaw_3d_viewer/')) {
            $names = preg_replace('|^\/oeaw_3d_viewer\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/oeaw_3d_viewer/$names";
        }

        if (0 === strpos($path, '/iiif_viewer/')) {
            $names = preg_replace('|^\/iiif_viewer\/|', '', $path);
            $names = str_replace('/',':', $names);
            return "/iiif_viewer/$names";
        }

        return $path;
    }
}
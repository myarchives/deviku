<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use function GuzzleHttp\json_decode;
use function Matrix\trace;
use Illuminate\Support\Facades\Redirect;
use DB;

class ProxyDriveController extends Controller
{
    //
    use HelperController;
    function index(Request $request)
    {
        $idDrive = $request->input('id');
        $videoName = $request->input('videoName');
        return Redirect::away($this->getVideoLinkProxy($idDrive, $videoName));
    }
    function getVideoLinkProxy($idDrive, $videoName)
    {
        $getlinkproxy = $this->viewsource("https://drive01.herokuapp.com/api/proxy/" . $idDrive . "?token=ndo&videoName=" . $videoName);
        $result = json_decode($getlinkproxy, true);
        if (isset($result['data'])) {
            $counts = count($result['data']);
            if ($counts == 0) {
                return null;
            } else {
                $parse1 = $result['data'];
                foreach ($parse1 as $a) {
                    if ($a['label'] == '360p') {
                        return $this->getLinkAndRedirect($a['src']);
                    } else {
                        if ($a['label'] == '480p') {
                            return $this->getLinkAndRedirect($a['src']);
                        } else {
                            return "https://www.googleapis.com/drive/v3/files/" . $idDrive . "?alt=media&key=AIzaSyARh3GYAD7zg3BFkGzuoqypfrjtt3bJH7M&name=" . $videoName . "-720p.mp4";
                        }
                    }
                }
            }
        } else {
            return $result['reason'];
        }
    }
    function getLinkAndRedirect($links)
    {
        $values = array("drive01.herokuapp.com", "drive03.herokuapp.com", "drive04.herokuapp.com", "drive02.herokuapp.com");
        return preg_replace_callback("/drive01.herokuapp.com/", function () use ($values) {
            return $values[array_rand($values)];
        }, $links);
    }
    function getBrokenLink($id)
    {
        $data = DB::table('contents')->whereIn('id', function ($query) {
            $query->from('brokenlinks')->select('contents_id')->get();
        })->where('drama_id', $id)->orderBy('title', 'asc')->get();
        if (!is_null($data)) {
            $returnData = null;
            foreach ($data as $content) {
                if (!$this->CheckHeaderCode($content->f720p)) {
                    $idDrive = $this->GetIdDrive($content->f360p);
                    if ($idDrive) {
                        $returnData .= $this->getVideoLinkProxy($idDrive, $content->url . "-720p") . "\n";
                    }
                }
                if (!$this->CheckHeaderCode($content->f360p)) {
                    $idDrive = $this->GetIdDrive($content->f720p);
                    if ($idDrive) {
                        $returnData .= $this->getVideoLinkProxy($idDrive, $content->url . "-360p") . "\n";
                    }
                }
            }
            return $returnData;
        }
        return response()->json("Nothing Broken link", 404);
    }
}
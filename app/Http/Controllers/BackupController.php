<?php

namespace App\Http\Controllers;

use App\BackupFilesDrive;
use App\gmail;
use DB;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use HelperController;
    public function __construct()
    {
        set_time_limit(180);
    }
    public function deletegdFromDB()
    {
        // $this->AutoDeleteGd();
        $dataresult = array();
        $datass = \App\Trash::take(20)->get();
        if ($datass) {
            foreach ($datass as $datass) {
                $idcopy = $datass->idcopy;
                $tokens = $datass->token;
                if (!is_null($idcopy) && !is_null($tokens)) {
                    if ($this->deletegd($this->GetIdDriveTrashed($idcopy), $tokens)) {
                        $datass->delete();
                        array_push($dataresult, $datass->idcopy . " Delete");
                    } else {
                        array_push($dataresult, $datass->idcopy . " Delete Error");
                    }
                } else {
                    $datass->delete();
                }
            }
        }
        return response()->json($dataresult, 200);
    }
    public function changeMaster()
    {
        $dataresult = [];
        DB::table('masterlinks')->whereNull('drive')->delete();
        $cekData = gmail::where('tipe', 'master')->inRandomOrder()->first();
        if ($cekData) {
            $dataContent = DB::table('contents')
                ->select("url", "f720p", "id")
                ->whereNotIn('id', DB::table('masterlinks')->where("kualitas", "720p")->pluck('content_id'))
                ->whereNotNull('f720p')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            if ($dataContent) {
                foreach ($dataContent as $content) {
                    $duplicateMaster = $this->duplicateMaster($content, "720p");
                    array_push($dataresult, $duplicateMaster);
                }
            } else {
                
                array_push($dataresult, Array("massage"=>"720p Not Found"));
            }
            $dataContent = DB::table('contents')
                ->select("url", "f360p as f720p", "id")
                ->whereNotIn('id', DB::table('masterlinks')->where("kualitas", "360p")->pluck('content_id'))
                ->whereNotNull('f360p')
                ->orderBy('id', 'desc')
                ->take(20)
                ->get();

            if ($dataContent) {
                foreach ($dataContent as $content) {
                    $duplicateMaster = $this->duplicateMaster($content, "360p");
                    array_push($dataresult, $duplicateMaster);
                }
            } else {

                array_push($dataresult, Array("massage"=>"360p Not Found"));
            }
            return $dataresult;
        } else {
            $errorMassage = array("name" => "Gmail Master Not found");
            array_push($dataresult, $errorMassage);
        }
        return $dataresult;
    }
    public function duplicateMaster($content, $kualitas)
    {
        $idDrive = $this->GetIdDrive($content->f720p);
        $f20p = $this->CheckHeaderFolderCode($idDrive);
        if (!$f20p) {
            $contentSetNull = \App\Content::where('url',$content->url)->first();
            if($contentSetNull){

                if ($kualitas == "720p") {
                    $contentSetNull->f720p = null;
                } else {
                    $contentSetNull->f360p = null;
    
                }
                $contentSetNull->save();
                return $content->url . " " . $kualitas . " Error Links " . $content->f720p;
            }else{
                return $content->url . "Cant Set Null " . $kualitas . " Error Links " . $content->f720p;
            }
        } else {
            $settingData = gmail::where('tipe', 'master')->inRandomOrder()->first();
            $data = array("status" => "duplicate", "url" => $content->url, "content_id" => $content->id, "kualitas" => $kualitas);
            $datass = \App\masterlinks::firstOrCreate($data);
            $copyID = $this->copygd($idDrive, $settingData->folderid, $content->url . "-" . $kualitas, $settingData->token);
            if (isset($copyID['id'])) {
                $this->changePermission($copyID['id'], $settingData->token);
                $datass->drive = $copyID['id'];
                $datass->apikey = $settingData->token;
                $datass->status = "success";
                $datass->save();
                return $datass;
            } else {
                $datass->delete();
                return $copyID;
            }

        }
    }
    public function index()
    {
        //
        $dataresult = array();
        $cekData = gmail::where('tipe', 'backup')->first();
        if ($cekData) {
            //$this->AutoDeleteGd();
            $dataContent = DB::table('masterlinks')
                ->whereNotIn('url', DB::table('backups')->where("title", "720p")->pluck('url'))
                ->where('status', 'success')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            foreach ($dataContent as $dataContents) {
                $settingData = gmail::where('tipe', 'backup')->inRandomOrder()->first();
                $datass = BackupFilesDrive::where(['url' => $dataContents->url, 'title' => "720p"]);
                if ($datass->count() < 1) {
                    $copyID = $this->copygd($this->GetIdDriveTrashed($dataContents->drive), $settingData->folderid, $dataContents->url . "-360p", $settingData->token);
                    if (isset($copyID['id'])) {
                        $this->changePermission($copyID['id'], $settingData->token);
                        // $datass = new BackupFilesDrive;
                        // $datass->f720p = $copyID['id'];
                        // $datass->url = $dataContents->url;
                        // $datass->title = "720p";
                        // $datass->tokenfcm = $settingData->token;
                        // $datass->save();
                        $datass = \App\masterlinks::updateOrCreate(
                            ['kualitas' => "720p", 'url'=>$dataContents->url],
                            ['apikey' => $settingData->token,  'f720p' => $copyID['id']]
                        );
                        array_push($dataresult, $datass);
                    } else {
                        array_push($dataresult, $copyID);
                    }
                }else {
                    array_push($dataresult, $datass);
                }
            }
            $dataContent = DB::table('masterlinks')
                ->whereNotIn('url', DB::table('backups')->where("title", "360p")->pluck('url'))
                ->where('status', 'success')
                ->whereNotNull('drive')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            foreach ($dataContent as $dataContents) {
                $settingData = gmail::where('tipe', 'backup')->inRandomOrder()->first();
                $datass = BackupFilesDrive::where(['url' => $dataContents->url, 'title' => "360p"]);
                if ($datass->count() < 1) {
                    $copyID = $this->copygd($this->GetIdDriveTrashed($dataContents->drive), $settingData->folderid, $dataContents->url . "-360p", $settingData->token);
                    if (isset($copyID['id']) && !isset($copyID['error'])) {
                        $this->changePermission($copyID['id'], $settingData->token);
                        // $datass = new BackupFilesDrive;
                        // $datass->f720p = $copyID['id'];
                        // $datass->url = $dataContents->url;
                        // $datass->title = "360p";
                        // $datass->tokenfcm = $settingData->token;
                        // $datass->save();
                        $datass = \App\masterlinks::updateOrCreate(
                            ['kualitas' => "360p", 'url'=>$dataContents->url],
                            ['apikey' => $settingData->token,  'drive' => $copyID['id']]
                        );
                        array_push($dataresult, $datass);
                    } else {
                        array_push($dataresult, $copyID);
                    }
                }else {
                    array_push($dataresult, $datass);
                }
            }
            DB::table('backups')->whereNull('f720p')->delete();
        } else {
            $errorMassage = array("name" => "Gmail Backups Nof found");
            array_push($dataresult, $errorMassage);
        }
        return response()->json($dataresult);
    }
    public function getMirrorAlternatif()
    {
        // $severDownload = $this->getProviderStatus("", "ServerDownload");
        // $this->viewsource(str_replace("mirror", "sync", $severDownload['keys']));
        $dataresult = array();

        $dataContent = DB::table('masterlinks')
            ->whereNotIn('drive', DB::table('mirrorcopies')->pluck('drive'))
            ->where('kualitas', '720p')->where('status', 'success')
            ->whereNotNull('drive')
            ->orderBy('id', 'asc')
            ->take(10)
            ->get();
        if ($dataContent) {
            foreach ($dataContent as $dataContents) {
                $f20p = $this->CheckHeaderFolderCode($dataContents->drive);
                if ($f20p) {
                    $fembed = $this->getMirror($dataContents->drive, "fembed.com");
                    // $rapid = $this->getMirror($dataContents->drive, "rapidvideo.com");
                    // $openload = $this->getMirror($dataContents->drive, "openload.com");
                    $copyID = array("fembed" => $fembed, "url" => $dataContents->url);
                    array_push($dataresult, $copyID);
                } else {
                    $content = DB::table('masterlinks')::find($dataContents->id);
                    $content->status = "broken";
                    $content->save();
                }
            }
        } else {
            array_push($dataresult, "Not Found Copy");
        }

        return response()->json($dataresult);
    }
    public function getMirror($data, $mirror)
    {
        switch ($mirror) {
            case "fembed.com":
                try {
                    return $this->fembedCopy($data, $mirror);
                } catch (Exception $e) {
                    return null;
                }
                break;
            case "rapidvideo.com":
                try {
                    return $this->RapidVideo($data, $mirror);
                } catch (Exception $e) {
                    return null;
                }
                break;
            case "openload.com":
                try {
                    return $this->openload($data, $mirror);
                } catch (Exception $e) {
                    return null;
                }
                break;
            case "drive.google.com":
                try {
                    return $this->googledrive($data, $mirror);
                } catch (Exception $e) {
                    return null;
                }
                break;
        }
    }
    public function getProviderStatus($data, $mirror)
    {
        return \App\mirrorkey::join('master_mirrors', 'master_mirrors.id', '=', 'mirrorkeys.master_mirror_id')->where(['master_mirrors.name' => $mirror])->inRandomOrder()->first();
    }
    public function GetIdDrive($urlVideoDrive)
    {
        if (preg_match('@https?://(?:[\w\-]+\.)*(?:drive|docs)\.google\.com/(?:(?:folderview|open|uc)\?(?:[\w\-\%]+=[\w\-\%]*&)*id=|(?:folder|file|document|presentation)/d/|spreadsheet/ccc\?(?:[\w\-\%]+=[\w\-\%]*&)*key=)([\w\-]{28,})@i', $urlVideoDrive, $id)) {
            return $id[1];
        } else {
            return $urlVideoDrive;
        }
    }
    public function syncFembed($data, $mirror)
    {
        $fembed = new \App\Classes\FEmbed();
        $apikey = $fembed->getKey($this->getProviderStatus($data, $mirror), $mirror);
        $dataCurl = $fembed->fembedCheck($apikey);
        if ($dataCurl['success']) {
            $arrayid = array();
            foreach ($dataCurl['data'] as $a => $b) {
                $apikeys = $apikey . "&task_id=" . $b['id'];
                $dataMirror = \App\Mirrorcopy::where('apikey', $apikeys)->first();
                if ($b['status'] == 'Task is completed') {
                    if ($dataMirror) {
                        $dataMirror->url = $b['file_id'];
                        $dataMirror->status = $b['status'];
                        $dataMirror->save();
                        array_push($arrayid, $b['id']);
                    } else {
                        array_push($arrayid, $b['id']);
                    }
                } elseif ($b['status'] == "Could not connect to download server") {
                    array_push($arrayid, $b['id']);
                    if ($dataMirror) {
                        $dataMirror->delete();
                    }
                } elseif ($b['status'] == "Not an allowed video file") {
                    array_push($arrayid, $b['id']);
                    if ($dataMirror) {
                        $dataMirror->delete();
                    }
                } elseif ($b['status'] == "Timed out") {
                    array_push($arrayid, $b['id']);
                    if ($dataMirror) {
                        $dataMirror->delete();
                    }
                } elseif ($b['status'] == "could not connect to server") {
                    array_push($arrayid, $b['id']);
                    if ($dataMirror) {
                        $dataMirror->delete();
                    }
                } elseif ($b['status'] == "could not verify file to download") {
                    array_push($arrayid, $b['id']);
                    if ($dataMirror) {
                        $dataMirror->delete();
                    }
                } elseif ($b['status'] == "file is too small, minimum allow size is 10,240 bytes") {
                    array_push($arrayid, $b['id']);
                    if ($dataMirror) {
                        $dataMirror->delete();
                    }
                } elseif ($b['status'] == "Downloading...") {
                    $dataDelete = \Carbon\Carbon::parse($b['created_at'])->addMinutes(30);
                    if ($dataDelete <= \Carbon\Carbon::now()) {
                        array_push($arrayid, $b['id']);
                        if ($dataMirror) {
                            $dataMirror->delete();
                        }
                    }
                }
            }
            if (!empty($arrayid)) {
                $apikeyremove = $apikey . "&remove_ids=" . json_encode($arrayid);
                $dataCurl = $fembed->fembedCheck($apikeyremove);
            }
            // dd($arrayid);
        }
    }
    public function fembedCopy($data, $mirror)
    {
        $response = [];
        $fembed = new \App\Classes\FEmbed();
        $ClientID = $this->getProviderStatus($data, $mirror);
        $this->syncFembed($data, $mirror);

        if ($ClientID != null) {
            $copies = \App\Mirrorcopy::where(['drive' => $data])->where(['provider' => $mirror])->first();
            if ($copies) {
                $url = null;
                if ($copies['status'] == "Task is completed") {
                    Cache::remember(md5($copies['url']), 3600 * 48, function () use ($data, $mirror, $fembed, $copies) {
                        $keys = $fembed->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&file_id=" . $copies['url'];
                        $dataCheck = $fembed->fembedFile($keys);
                        if ($dataCheck['data']['status'] != 'Live') {
                            $copies->delete();
                        }
                    });
                    return "https://www.fembed.com/v/" . $copies['url'];
                } else {
                    return "";
                    // });
                }
                return $url;
            } else {
                if ($ClientID['status'] == "Up") {
                    $urlDownload = [];
                    $nameVideo = md5($data);
                    $driveId = $this->GetIdDrive($data);
                    //$severDownload = $this->getProviderStatus($data, "ServerDownload");
                    //$urlVideoDriveNode = $severDownload['keys'] . "/" . $driveId . "/" . $nameVideo . ".mp4";
                    $urlVideoDriveNode = "https://www.googleapis.com/drive/v3/files/" . $driveId . "?alt=media";
                    // $urlDownloadLink = $this->viewsource($urlVideoDriveNode);
                    $email = gmail::where('tipe', 'copy')->inRandomOrder()->first();
                    $headerBuild = array("Authorization" => $this->get_token($email->token));
                    $urlDownload[] = array("link" => $urlVideoDriveNode, "headers" => $headerBuild);
                    $datacurl = $fembed->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&links=" . json_encode($urlDownload);
                    $resultCurl = $fembed->fembedUpload($datacurl);
                    if ($resultCurl['success']) {
                        $mirrorcopies = new \App\Mirrorcopy();
                        $mirrorcopies->url = null;
                        $mirrorcopies->status = "uploaded";
                        $mirrorcopies->drive = $data;
                        $mirrorcopies->provider = $mirror;
                        $mirrorcopies->apikey = $fembed->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&task_id=" . $resultCurl['data'][0];
                        $mirrorcopies->save();
                        return $resultCurl['data'][0];
                    } else {
                        return $resultCurl;
                    }
                } else {
                    return "Status ".$ClientID['status'];
                }
            }
        } else {
            return "";
        }
    }

    public function RapidVideo($data, $mirror)
    {
        $ClientID = $this->getProviderStatus($data, $mirror);
        if (is_null($ClientID)) {
            return "";
        } else {
            $rapidvideo = new \App\Classes\RapidVideo();
            $copies = \App\Mirrorcopy::where(['drive' => $data])->where(['provider' => $mirror])->first();
            if (is_null($copies)) {
                if ($ClientID['status'] == "Up") {
                    $nameVideo = md5($data);
                    $driveId = $this->GetIdDrive($data);
                    $severDownload = $this->getProviderStatus($data, "ServerDownload");
                    $urlDownload = $severDownload['keys'] . "/" . $driveId . "/" . $nameVideo . ".mp4";
                    $datacurl = $rapidvideo->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&url=" . $urlDownload;
                    $resultCurl = $rapidvideo->RapidVideoUpload($datacurl);
                    if ($resultCurl['status'] == "OK") {
                        $mirrorcopies = new \App\Mirrorcopy();
                        $mirrorcopies->url = null;
                        $mirrorcopies->status = "uploaded";
                        $mirrorcopies->drive = $data;
                        $mirrorcopies->provider = $mirror;
                        $mirrorcopies->apikey = $rapidvideo->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&id=" . $resultCurl['id'];
                        $mirrorcopies->save();
                    }
                    return "";
                }
                return "";
            } else {
                $urlID = "";
                if ($copies['status'] == "uploaded") {
                    $checkResult = $rapidvideo->RapidVideoStatus($copies['apikey']);
                    if ($checkResult['msg'] == "OK") {
                        foreach ($checkResult['result'] as $a => $b) {
                            if ($b['status'] == 'finished') {
                                $copies->url = $b['extid'];
                                $copies->status = "Task is completed";
                                $copies->save();
                                $urlID = "https://www.rapidvideo.com/v/" . $b['extid'];
                            }
                        }
                    }
                    return $urlID;
                }
                return "https://www.rapidvideo.com/v/" . $copies['url'];
            }
        }
    }
    public function openload($data, $mirror)
    {
        $ClientID = $this->getProviderStatus($data, $mirror);
        if (is_null($ClientID)) {
            return "";
        } else {
            $openload = new \App\Classes\Openload();
            $copies = \App\Mirrorcopy::where(['drive' => $data])->where(['provider' => $mirror])->first();
            if (is_null($copies)) {
                if ($ClientID['status'] != "Up") {
                    return "";
                }
                $nameVideo = md5($data);
                $driveId = $this->GetIdDrive($data);
                $severDownload = $this->getProviderStatus($data, "ServerDownload");
                $urlDownload = $severDownload['keys'] . "/" . $driveId . "/" . $nameVideo . ".mp4";
                $datacurl = $openload->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&url=" . $urlDownload;
                $resultCurl = $openload->OpenloadUpload($datacurl);
                if ($resultCurl['msg'] != "OK") {
                    return "";
                }
                $mirrorcopies = new \App\Mirrorcopy();
                $mirrorcopies->url = null;
                $mirrorcopies->status = "uploaded";
                $mirrorcopies->drive = $data;
                $mirrorcopies->provider = $mirror;
                $mirrorcopies->apikey = $openload->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&id=" . $resultCurl['id'];
                $mirrorcopies->save();
                return "";
            } else {
                $urlID = "";
                if ($copies['status'] == "uploaded") {
                    $checkResult = $openload->OpenloadStatus($copies['apikey']);
                    if ($checkResult['msg'] == "OK") {
                        foreach ($checkResult['result'] as $a => $b) {
                            if ($b['status'] == 'finished') {
                                $copies->url = $b['extid'];
                                $copies->status = "Task is completed";
                                $copies->save();
                                $keys = $openload->getKey($this->getProviderStatus($data, $mirror), $mirror) . "&id=" . $resultCurl['id'];
                                if ($copies['apikey'] == $keys) {
                                    $urlID = "http://oload.stream/f/" . $b['extid'];
                                }
                            }
                        }
                    }
                    return $urlID;
                }
                return "https://oload.stream/f/" . $copies['url'];
            }
        }
    }
    public function googledrive($data, $mirror)
    {
        $ClientID = $this->getProviderStatus($data, $mirror);
        if (is_null($ClientID)) {
            return "";
        } else {
            $googledrive = new \App\Classes\GoogleDriveAPIS();
            $copies = \App\Mirrorcopy::where(['drive' => $data])->where(['provider' => $mirror])->first();
            if (!is_null($copies)) {
                return $this->GetPlayer($copies['url']);
            } else {
                if ($ClientID['status'] != "Up") {
                    return null;
                }
                $keys = $this->getProviderStatus($data, $mirror);
                $driveId = $this->GetIdDrive($data);
                $copyID = $googledrive->GDCopy($driveId, $keys);
                if (is_null($copyID) || isset($copyID['error'])) {
                    return "";
                };
                $mirrorcopies = new \App\Mirrorcopy();
                $mirrorcopies->url = $copyID;
                $mirrorcopies->status = "Task is completed";
                $mirrorcopies->drive = $data;
                $mirrorcopies->provider = $mirror;
                $mirrorcopies->apikey = $keys['keys'];
                $mirrorcopies->save();
                return $this->GetPlayer($copyID);
            }
        }
    }
    public function googledriveBackup($data, $mirror)
    {
        $ClientID = $this->getProviderStatus($data, $mirror);
        if (is_null($ClientID)) {
            return "";
        } else {
            $googledrive = new \App\Classes\GoogleDriveAPIS();
            $copies = \App\Mirrorcopy::where(['drive' => $data])->where(['provider' => $mirror])->first();
            if (!is_null($copies)) {
                return false;
            } else {
                if ($ClientID['status'] != "Up") {
                    return false;
                }
                $keys = $this->getProviderStatus($data, $mirror);
                $driveId = $this->GetIdDrive($data);
                $copyID = $googledrive->GDCopy($driveId, $keys);
                if (is_null($copyID) || isset($copyID['error'])) {
                    return false;
                };
                $mirrorcopies = new \App\Mirrorcopy();
                $mirrorcopies->url = $copyID;
                $mirrorcopies->status = "Task is Completed";
                $mirrorcopies->drive = $data;
                $mirrorcopies->provider = $mirror;
                $mirrorcopies->apikey = $keys['keys'];
                $mirrorcopies->save();
                return true;
            }
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\backup  $backup
     * @return \Illuminate\Http\Response
     */
    public function show(backup $backup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\backup  $backup
     * @return \Illuminate\Http\Response
     */
    public function edit(backup $backup)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\backup  $backup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, backup $backup)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\backup  $backup
     * @return \Illuminate\Http\Response
     */
    public function destroy(backup $backup)
    {
        //
    }
    public function testgd(Request $request)
    {
        $id = $this->my_simple_crypt($request->input('id'), "d");
        $link = "https://drive.google.com/uc?export=download&id=" . $id;
        $goutteClient = new Client();
        $guzzleClient = new GuzzleClient(array(
            'timeout' => 60,
            'verify' => false,
            'cookies' => true,
        ));
        $goutteClient->setClient($guzzleClient);
        $crawler = $goutteClient->request('GET', $link);
        // dd( $crawler->selectLink('Download anyway')->link());
        $cookieJar = $goutteClient->getCookieJar();
        $link = $crawler->filter('#uc-download-link')->eq(0)->attr('href');
        $tmp = explode("confirm=", $link);
        $tmp2 = explode("&", $tmp[1]);
        $confirm = $tmp2[0];
        $linkdowngoc = "https://drive.google.com/uc?export=download&id=$id&confirm=$confirm";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $linkdowngoc);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/google.mp3");
        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . "/google.mp3");

        // Getting binary data
        $page = curl_exec($ch);
        $get = $this->locheader($page);

        // }
        curl_close($ch);
        return $get;
        return $crawler;
    }
    public function locheader($page)
    {
        $temp = explode("\r\n", $page);
        foreach ($temp as $item) {
            $temp2 = explode(": ", $item);
            if (isset($temp2[1])) {
                $infoheader[$temp2[0]] = $temp2[1];
            }
        }
        if (!isset($infoheader['Location'])) {
            return "";
        }
        $location = $infoheader['Location'];
        return $location;
    }
    public function my_simple_crypt($string, $action = 'e')
    {
        $secret_key = 'GReg7rNx2z[2';
        $secret_iv = 'C0?s9rh4';
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ($action == 'e') {
            $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        } elseif ($action == 'd') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }
}

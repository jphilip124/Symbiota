<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/FieldGuideManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$resultId = array_key_exists("resid",$_REQUEST)?$_REQUEST["resid"]:0;
$viewMode = array_key_exists("viewmode",$_REQUEST)?$_REQUEST["viewmode"]:'full';
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:100;

$apiManager = new FieldGuideManager();
$cleanManager = new OccurrenceCleaner();
$resultArr = array();
$statusStr = '';

if($collId) $cleanManager->setCollId($collId);
$collMap = $cleanManager->getCollMap();

$isEditor = 0;
if($SYMB_UID){
    if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
        $isEditor = 1;
    }
}

if($isEditor){
    $apiManager->setCollID($collId);
    if($action == 'Add Determinations'){
        $apiManager->processDeterminations($_POST);
        $statusStr = 'Determinations added';
    }
    if($resultId){
        $apiManager->setJobID($resultId);
        $apiManager->setViewMode($viewMode);
        $apiManager->primeFGResults();
        $apiManager->processFGResults();
        $resultArr = $apiManager->getResults();
        $tidArr = $apiManager->getTids();
    }
}
?>
<html>
<head>
    <title><?php echo $collMetadata['collectionname']; ?> FieldGuide Results Viewer</title>
    <link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <link rel="stylesheet" href="../../css/jquery-ui.css" type="text/css" />
    <script type="text/javascript" src="../../js/jquery.js"></script>
    <script type="text/javascript" src="../../js/jquery-ui.js"></script>
    <script type="text/javascript" src="../../js/symb/shared.js"></script>
    <script type="text/javascript">
        function validateForm(f){
            var dbElements = document.getElementsByName("occid[]");
            for(i = 0; i < dbElements.length; i++){
                var dbElement = dbElements[i];
                if(dbElement.checked) return true;
            }
            alert("Please select specimens to be determined!");
            return false;
        }

        function selectAll(f){
            var boxesChecked = true;
            if(!f.selectall.checked){
                boxesChecked = false;
            }
            var dbElements = document.getElementsByName("occid[]");
            for(i = 0; i < dbElements.length; i++){
                dbElements[i].checked = boxesChecked;
            }

        }

        function submitViewForm(f){
            f.submit();
        }
    </script>
</head>
<body style="background-color:white;margin-left:0px;margin-right:0px">
    <div class='navpath'>
        <a href='../../index.php'>Home</a> &gt;&gt;
        <a href='collprofiles.php?emode=1&collid=<?php echo $collId; ?>'>Collection Management</a> &gt;&gt;
        <b>FieldGuide Results Viewer</b>
    </div>

    <!-- inner text -->
    <div id="innertext" style="background-color:white;">
        <?php
        echo '<h2 style="margin-top:0px;margin-bottom:0px;">'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
        if($statusStr){
            ?>
            <hr/>
            <div style="margin:15px;color:red;">
                <?php echo $statusStr; ?>
            </div>
            <hr/>
            <?php
        }
        if($isEditor){
            if($resultArr){
                ?>
                <form name="viewform" action="fgresults.php" method="post" onsubmit="">
                    <div style="width:250px;height:10px;">
                        <div style="float:left;">
                            <input name="viewmode" type="radio" value="full" <?php echo ($viewMode == 'full'?'checked':''); ?> onchange="submitViewForm(this.form);" /> Full Results
                        </div>
                        <div style="float:right;">
                            <input name="viewmode" type="radio" value="filtered" <?php echo ($viewMode == 'filtered'?'checked':''); ?> onchange="submitViewForm(this.form);" /> Filtered Results
                        </div>
                    </div>
                    <input name="collid" type="hidden" value="<?php echo $collId; ?>" />
                    <input name="resid" type="hidden" value="<?php echo $resultId; ?>" />
                    <input name="start" type="hidden" value="<?php echo $start; ?>" />
                </form>
                <div style="">
                    <b>Use the checkboxes to select the records you would like to add determinations, and the radio buttons to select which determination to add.</b>
                </div>
                <form name="fgbatchidform" action="fgresults.php" method="post" onsubmit="return validateForm(this);">
                    <?php
                    $recCnt = count($resultArr);
                    if($recCnt > $limit){
                        $href = 'fgresults.php?collid='.$collId.'&resid='.$resultId.'&viewmode='.$viewMode.'&start='.($start+$limit);
                        echo '<div style="float:right;"><a href="'.$href.'"><b>NEXT '.$limit.' RESULTS &gt;&gt;</b></a></div>';
                    }
                    echo '<div><b>'.($start+1).' to '.($start+$recCnt).' Results </b></div>';
                    if($start > 0){
                        $resultArr = array_slice($resultArr,$start);
                    }
                    ?>
                    <table class="styledtable" style="font-family:Arial;font-size:12px;">
                        <tr>
                            <th style="width:40px;">Record ID</th>
                            <th style="width:20px;"><input name="selectall" type="checkbox" title="Select/Deselect All" onclick="selectAll(this.form)" /></th>
                            <th>Current Identification</th>
                            <th></th>
                            <th></th>
                            <th>FieldGuide Identification</th>
                        </tr>
                        <?php
                        $setCnt = 0;
                        $prevOccId = 0;
                        $prevImgId = 0;
                        $currID = '';
                        foreach($resultArr as $occId => $occArr){
                            if($prevOccId != $occId){
                                $prevOccId = $occId;
                                $setCnt++;
                                $firstOcc = true;
                                $firstRadio = true;
                                $currID = $occArr['sciname'];
                                unset($occArr['sciname']);
                            }
                            foreach($occArr as $imgId => $imgArr){
                                if($prevImgId != $imgId){
                                    $prevImgId = $imgId;
                                    $imgurl = $imgArr['url'];
                                    $fgidarr = $imgArr['results'];
                                    $firstImg = true;
                                }
                                foreach($fgidarr as $name){
                                    $valid = false;
                                    $note = '';
                                    $tId = 0;
                                    if(array_key_exists($name,$tidArr)){
                                        if(count($tidArr[$name]) == 1){
                                            $valid = true;
                                            $tId = $tidArr[$name][0];
                                        }
                                        else{
                                            $note = 'Name ambiguous';
                                        }
                                    }
                                    else{
                                        $note = 'Not in thesaurus';
                                    }
                                    if($note) $name = $name.' <span style="color:red;">'.$note.'</span>';
                                    echo '<tr '.(($setCnt % 2) == 1?'class="alt"':'').'>';
                                    echo '<td>'."\n";
                                    if($firstOcc) echo '<a href="../editor/occurrenceeditor.php?occid='.$occId.'" target="_blank">'.$occId.'</a>'."\n";
                                    echo '</td>'."\n";
                                    echo '<td>'."\n";
                                    if($firstOcc) echo '<input name="occid[]" type="checkbox" value="'.$occId.'" />'."\n";
                                    echo '</td>'."\n";
                                    echo '<td>'."\n";
                                    if($firstOcc) echo '<a href="'.$CLIENT_ROOT.'/taxa/index.php?taxon='.$currID.'" target="_blank">'.$currID.'</a>'."\n";
                                    echo '</td>'."\n";
                                    echo '<td>'."\n";
                                    if($firstImg) echo '<a href="'.$imgurl.'" target="_blank">View Image</a>'."\n";
                                    echo '</td>'."\n";
                                    echo '<td>'."\n";
                                    if($valid && ($currID != $name)) echo '<input name="id'.$occId.'" type="radio" value="'.$tId.'" '.($firstRadio?'checked':'').'/>'."\n";
                                    echo '</td>'."\n";
                                    echo '<td><a href="'.$CLIENT_ROOT.'/taxa/index.php?taxon='.$name.'" target="_blank">'.$name.'</a></td>'."\n";
                                    $firstOcc = false;
                                    $firstImg = false;
                                    if($valid) $firstRadio = false;
                                }
                            }
                        }
                        ?>
                    </table>
                    <div style="margin:15px;">
                        <input name="collid" type="hidden" value="<?php echo $collId; ?>" />
                        <input name="resid" type="hidden" value="<?php echo $resultId; ?>" />
                        <input name="start" type="hidden" value="<?php echo $start; ?>" />
                        <input name="viewmode" type="hidden" value="<?php echo $viewMode; ?>" />
                        <input name="action" type="submit" value="Add Determinations" />
                    </div>
                </form>
                <?php
            }
            else{
                echo '<p><b>No results to display</b></p>';
            }
            ?>
            <div>
                <a href="fgbatch.php?collid=<?php echo $collId; ?>">Return to FieldGuide Batch Processing</a>
            </div>
            <?php
        }
        else{
            echo '<h2>You are not authorized to access this page</h2>';
        }
        ?>
    </div>
</body>
</html>
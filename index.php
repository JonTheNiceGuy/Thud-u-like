<html><head><title>THUD-u-LIKE</title></head><body><table><tr><td><form METHOD="post" ACTION="index.php">

<?php

global $role,$cell,$state,$point,$board;

mysql_connect("localhost","root","");
mysql_selectdb("thud");

$strStatus="Opening";

/*******************/
/* THUD - u - LIKE */
/*******************/

$author_name="Jon Spriggs";
$author_mail="jon@spriggs.org.uk";
$copyright="2004";

$origins="This is an online version of the game of Thud, originally developed by Terry Pratchett and Trevor Truran";

switch($_POST['state'] . $_GET['state']) {

  case "choosepiece":
    doCheckMove($_GET['cell'],$_GET['gameid'],$_GET['turnid']);
    doDrawBoard($_GET['gameid'],$_GET['turnid']);
    break;

  case "invite":
    doSendInvite();
    break;

  case "login":
    doGameInit();
    break;

  default:
    doLogin();
    break;
}

echo "</td><td width=30% height=400>I'd have the I can't win button here.<hr>$origins<br><br>Author: <a href='mailto:$author_mail'>$author_name</a><br><br>Copyright $author_name $copyright<hr></td></tr></table></form></body></html>";

/*****************************
 * Game functions start here *
 *****************************/

function doLogin() {
  echo "<!-- doLogin -->\n";
  echo "<h1>Sign in</h1>\n";
  echo "<input type=hidden name=state value=login>\n";
  echo "<table width=100%>\n";
  echo "<tr><td>e-Mail address:</td><td><input type=text name=email size=40></td></tr>\n";
  echo "<tr><td>Log in</td><td><input type=submit name=submit value='Click Here'></td></tr>\n";
  echo "</table>\n";
}

function doGameInit() {
  echo "<!-- doGameInit -->\n";
  
  $sqlGetGames="SELECT `game_uid`, `strPlayer1`, `strPlayer2`, `intTurn` FROM games WHERE strPlayer1='" . $_POST['email'] . "' OR strPlayer2='" . $_POST['email'] . "'";
  $qryGetGames=mysql_query($sqlGetGames);

  if (mysql_num_rows($qryGetGames)>0) {
    echo "<h1>Existing Games!</h1>\n";
    echo "<table width=100%>\n";
    echo "<tr><th>Player 1</th><th>Player 2</th><th>Play!</th></tr>\n";
    while (list($gameID, $strPlayer1, $strPlayer2, $intTurn)=mysql_fetch_array($qryGetGames)) {
      if ($intTurn==1 AND $strPlayer1==$_POST['email']) {
        echo "<tr><td><b>$strPlayer1</b></td><td>$strPlayer2<td><input type=submit name=gameid value='$gameID'></td></tr>\n";
      } elseif ($intTurn==2 AND $strPlayer2==$_POST['email']) {
        echo "<tr><td>$strPlayer1</td><td><b>$strPlayer2</b><td><input type=submit name=gameid value='$gameID'></td></tr>\n";
      } else {
        echo "<tr><td>$strPlayer1</td><td>$strPlayer2<td>Waiting...</td></tr>\n";
      }
    }
    echo "</table>\n";
  }
  echo "<h1>Invite Player!</h1>\n";
  echo "<input type=hidden name=state value=invite>\n";
  echo "<input type=hidden name=email value='" . $_POST['email'] . "'>\n";
  echo "<table width=100%>\n";
  echo "<tr><td>e-mail address:</td><td><input type=text name=invite size=40></td></tr>\n";
  echo "<tr><td>Invite</td><td><input type=submit name=submit value='Click Here'></td></tr>\n";    
  echo "</table>\n";
}

function doSendInvite() {
  if($_POST['gameid']=="") {
    $sqlStartGame="INSERT INTO `games` (`strPlayer1` , `strPlayer2` ) VALUES ('" . $_POST['email'] . "', '" . $_POST['invite'] . "')";
    echo "<!-- Insert: $sqlStartGame -->\n";
    $qryStartGame=mysql_query($sqlStartGame);
    $sqlGetGame="SELECT game_uid FROM games WHERE strPlayer1='". $_POST['email'] . "' AND strPlayer2='" . $_POST['invite'] . "' AND intTurn=1 ORDER BY game_uid DESC LIMIT 1";
    echo "<!-- Select: $sqlGetGame -->\n";
    $qryGetGame=mysql_query($sqlGetGame);
    list($gameID)=mysql_fetch_array($qryGetGame);
    $turnID=1;
  } else {
    $gameID=$_POST['gameid'];
    $sqlGetTurn="SELECT intTurn FROM games WHERE game_uid=" . $_POST['gameid'];
    $qryGetTurn=mysql_query($sqlGetTurn);
    list($turnID)=mysql_fetch_array($qryGetTurn);
  }
  doDrawBoard($gameID,$turnID);
}

function doBoardInit($gameID) {
  global $board;
  echo "<!-- doBoardInit -->\n";
  // Piece ID's are as follows
  // 0 = Empty
  // 1 = Dwarf
  // 2 = Troll
  // 9 = Not Available Space

  $sqlConfirmBoard="SELECT count(intGameUID) as count_of_intGameUID FROM game_boards WHERE intGameUID='" . $gameID . "'";
  $qryConfirmBoard=mysql_query($sqlConfirmBoard);
  $aryConfirmBoard=mysql_fetch_array($qryConfirmBoard);

  if($aryGetBoard['count_of_intGameUID']!=225) {
    $sqlGetBoard="SELECT strCellID, intCellValue FROM default_board";
    $qryGetBoard=mysql_query($sqlGetBoard);
    $doCreateBoard=TRUE;
  } else {
    $sqlGetBoard="SELECT strCellID, intCellValue FROM game_boards WHERE intGameUID='" . $gameID . "'";
    $qryGetBoard=mysql_query($sqlGetBoard);
  }
  while (list($strCellID,$intCellValue)=mysql_fetch_array($qryGetBoard)) {
    $board[$strCellID]=$intCellValue;
    if($doCreateBoard==TRUE) {
      $sqlCreateBoard="INSERT INTO game_boards (intGameUID, strCellID, intCellValue) VALUES ('" . $gameID . "', '" . $strCellID . "', '" . $intCellValue ."')";
      mysql_query($sqlCreateBoard);
    }
  }
  if($doCreateBoard==TRUE) {
    $sqlConfirmBoard="SELECT count(intGameUID) as count_of_intGameUID FROM game_boards WHERE intGameUID='" . $gameID . "'";
    $qryConfirmBoard=mysql_query($sqlConfirmBoard);
    $aryConfirmBoard=mysql_fetch_array($qryConfirmBoard);
  }
  echo "<!-- doBoardInit Complete -->\n";
}

function doDrawBoard($gameID,$turnID) {
  global $board;
  echo "<!-- doDrawBoard -->\n";
  doBoardInit($gameID);
  echo "<input type=hidden name=gameid value=$gameID>\n";
  echo "<table border=1>\n";
  for ($drow=1; $drow<=15; $drow++) {
    $row=strtoupper(dechex($drow));
    echo "<tr>\n";
    for ($dcol=1; $dcol<=15; $dcol++) {
      $col=strtoupper(dechex($dcol));
      switch($board[$row.$col]) {
        case "0":
        $strColour="bgcolor=white";
        break;
        case "1":
        $strColour="bgcolor=cyan";
        break;
        case "2":
        $strColour="bgcolor=magenta";
        break;
        case "9":
        $strColour="bgcolor=black";
        break;
      }
      echo "<td valign=center align=center height=30 width=25 $strColour>" . doShowSquare($row,$col,$gameID,$turnID) . "</td>\n";
    }
  echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<!-- doDrawBoard Complete -->\n";
}

function doShowSquare($row,$col,$gameID,$turnID) {
  $intRole=doCheckRole($gameID,$turnID);

  global $board, $point;
  $Dcode="D";
  $Tcode="T";
  $Ecode="&nbsp;";
  $Vcode="<input type=radio name=cell value={$row}{$col}>";

  if ($intRole==1) {
    $Dcode="<a href=index.php?state=choosepiece&cell={$row}{$col}&gameid=$gameID&turnid=$turnID>D</a>";
  } elseif ($intRole==2) {
    $Tcode="<a href=index.php?state=choosepiece&cell={$row}{$col}&gameid=$gameID&turnid=$turnID>T</a>";
  }
  
  switch($board[$row.$col]) {
    case "0":
      return "$Ecode";
      break;
    case "1":
      return "$Dcode";
      break;
    case "2":
      return "$Tcode";
      break;
    case "5":
      return "$Vcode";
      break;
    default:
      return "&nbsp;";
      break;
  }
}

function doCheckMove($cell,$gameID,$turnID) {
  global $board;
  $startx=hexdec($cell{0});
  $starty=hexdec($cell{1});
  $intRole=doCheckRole($gameID,$turnID);
  // 1-+ 2*+ 3++
  //
  // 8-* S** 4+*
  //
  // 7-- 6*- 5+-
  
  if($intRole==1) {$intMove=15;} else {$intMove=1;}
  
  $intMove1=0;
  $intMove2=0;
  $intMove3=0;
  $intMove4=0;
  $intMove5=0;
  $intMove6=0;
  $intMove7=0;
  $intMove8=0;
  
  for($intStep=1; $intStep<=$intMove; $intStep++) {
    $strMove1=dechex($startx-$intStep).dechex($starty+$intStep);
    $strMove2=dechex($startx).dechex($starty+$intStep);
    $strMove3=dechex($startx+$intStep).dechex($starty+$intStep);
    $strMove4=dechex($startx+$intStep).dechex($starty);
    $strMove5=dechex($startx+$intStep).dechex($starty-$intStep);
    $strMove6=dechex($startx).dechex($starty-$intStep);
    $strMove7=dechex($startx-$intStep).dechex($starty-$intStep);
    $strMove8=dechex($startx-$intStep).dechex($starty);
    
    if(($board[$strMove1]==0 OR ($board[$strMove1]==1 AND $intRole=2)) AND $stopMove1!=1) {
      $board[$strMove1]=5;
      $intMove1++;
    }
    if($board[$strMove2]==0 OR ($board[$strMove2]==1 AND $intRole=2)) {$board[$strMove2]=5;}
    if($board[$strMove3]==0 OR ($board[$strMove3]==1 AND $intRole=2)) {$board[$strMove3]=5;}
    if($board[$strMove4]==0 OR ($board[$strMove4]==1 AND $intRole=2)) {$board[$strMove4]=5;}
    if($board[$strMove5]==0 OR ($board[$strMove5]==1 AND $intRole=2)) {$board[$strMove5]=5;}
    if($board[$strMove6]==0 OR ($board[$strMove6]==1 AND $intRole=2)) {$board[$strMove6]=5;}
    if($board[$strMove7]==0 OR ($board[$strMove7]==1 AND $intRole=2)) {$board[$strMove7]=5;}
    if($board[$strMove8]==0 OR ($board[$strMove8]==1 AND $intRole=2)) {$board[$strMove8]=5;}
  }

}

function doCheckRole($gameID, $turnID) {
  $sqlReadTurn="SELECT intRole FROM games WHERE game_uid=$gameID";
  $qryReadTurn=mysql_query($sqlReadTurn);
  list($intRole)=mysql_fetch_array($qryReadTurn);
  
  return $intRole;
}

?>

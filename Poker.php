<?php

/*
=============================================
作成者：秋野浩朗
作成日：2020/7/20
修正日：2020/8/4
概要　：ポーカー
=============================================

■ 参考URL


*/ 

session_start();

// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　変数（ローカルファイル内の共通変数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

$source_name		= basename(__FILE__);
$delimiter			= "<br><hr><br>";
$err_msg				= null;

$stock_def	= array(
	array(1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 10, 11, 12, 13),
	array(1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 10, 11, 12, 13),
	array(1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 10, 11, 12, 13),
	array(1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 10, 11, 12, 13)
);
$suit_def			= array("spade", "heart", "club", "dia");
$hand_list		= array("ロイヤルストレートフラッシュ", "ストレートフラッシュ", "フォーカード", "フルハウス", "フラッシュ", 
											"ストレート", "スリーカード", "ツーペア", "ワンペア", "ハイカード（役なし）");
$hand_mag			= array(100, 50, 20, 7, 5, 4, 3, 2, 1, 1);
$stock				= $stock_def;
$suit					= $suit_def;
$stock_count	= 0;
$poker_hand		= "";
$count				= 0;
$verification_hand = "";		// 手札を強制操作時の役パターン

// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　関数（ローカルファイル内の共通関数）＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

/*【関数】サニタイズ（XSS対策） */
function h($val){
	return htmlspecialchars($val);
}

/*【関数】検証用（ preタグ + var_dump() ）*/
function pre_dump($arg){
	echo "<pre>";
	var_dump($arg);
	echo "</pre>";
}

//【関数】山札からカードを１枚取得して山札から削除する
// 引数：山札カードリスト
// 返値：取得したカード（柄=>数値の配列）
// 備考：山札はGlobal変数として取得したカードのデータを削除する。
function draw_card($stock){
	global $stock, $count;

	// pre_dump($stock);

	while(true){
		$count++;

		// 柄と数値をランダムで生成
		$suit_no			= rand(0, 3);
		$card_number	= rand(0, 12);

		if(isset($stock[$suit_no][$card_number])){
			// 生成した柄と数値があればカードを取得して山札から削除する
			$result[$suit_no]	= $stock[$suit_no][$card_number];
			unset($stock[$suit_no][$card_number]);
			break;
		}else{
			// 該当のカードが山札になければもう一度行う。
			continue;
		}
	}
	return $result;
}

//【関数】山札の残り枚数を取得する
// 引数：山札カードリスト
// 返値：山札の残り枚数（数値型）
function get_stoc_count($stock){
	$stock_count = 0;
	for($i = 0 ; $i < 4 ; $i++){
		$stock_count += count($stock[$i]) ;
	}
	return (int)$stock_count;
}

//【関数】配列の添え字を0から振りなおす
// 引数：配列
// 返値：添字を0から振り直した同じ配列
function alignment_array($array){
	$i = 0;
	$result_array = array();
	foreach($array as $val){
		// echo $i ,":", $val , "<br>";
		$result_array[$i] = $val;
		$i++;
	}
	return $result_array;
}

//【関数】配列の要素を数える（空値の要素はカウントしない）
// 引数：配列
// 返値：空値以外の要素の数（数値）
function count_array($array){
	$i=0;
	foreach($array as $val){
		if($val != ""){
			$i++;
		}
	}
	return $i;
}

//【関数】手札の役を判定する
// 引数：手札(数値リスト、スートリスト)
// 返値：該当役と一致する$hand_list（役名リスト配列）の添字（数値）
function judge_hand($number_list, $suit_list){
	// 手札の柄が全て同じかどうか（柄の内、重複しているグループの数が１つかどうか）
	if( count( array_unique($suit_list) ) == 1) {
		// 手札の数値が1,10,11,12,13 かどうか
		if( count( array_intersect($number_list, array(1,10,11,12,13) ) ) == 5) {
			// ロイヤルストレートフラッシュ　100倍
			return 0 ;
		}
		// 手札の数値を昇順にソート（キーの維持なし）
		$sort_array = $number_list;
		sort($sort_array);
		// ソートした配列の最後の数値と最初の数値の差分を判定
		if($sort_array[3] - $sort_array[2] == 1 &&
				$sort_array[2] - $sort_array[1] == 1 &&
				$sort_array[1] - $sort_array[0] == 1){
			// ストレートフラッシュ　50倍
			return 1;
		}
		// フラッシュ　5倍
		return 4;
	}

	// 手札の数値の内、重複しているグループが２つかどうか
	if( count( array_unique($number_list) ) == 2) {
		// 手札の数値の内、重複している値の重複数を取得
		$duplicate_count = array_count_values($number_list);
		foreach($duplicate_count as $val){
			if($val == 1 || $val == 4){
				// フォーカード　20倍
				return 2;
			}
		}
		// フルハウス　7倍
		return 3;
	}

	// 手札の数値を昇順にソート（キーの維持なし）
	$sort_array = $number_list;
	sort($sort_array);
	// ソートした配列の最後の数値と最初の数値の差分を判定
	if($sort_array[3] - $sort_array[2] == 1 &&
			$sort_array[2] - $sort_array[1] == 1 &&
			$sort_array[1] - $sort_array[0] == 1){
		// ストレート　4倍
		return 5;
	}

	// 手札の数値の内、重複しているグループが３つかどうか
	if( count( array_unique($number_list) ) == 3) {
		// 手札の数値の内、重複している値の重複数を取得
		$duplicate_count = array_count_values($number_list);
		foreach($duplicate_count as $val){
			if($val == 3 ){
				// スリーカード　3倍
				return 6;
			}
		}
		// ツーペア　2倍
		return 7;
	}

	// 手札の数値の内、重複しているグループが４つかどうか
	if( count( array_unique($number_list) ) == 4) {
		// ワンペア　1倍
		return 8;
	}
	// ブタ（役なし）　1倍
	return 9;
}

// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　以降、各種処理　＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝


if($_SERVER["REQUEST_METHOD"] == "POST"){
	// POST送信されたデータの取得
	$player_hand_card_number	= $_POST["player_hand_card_number"];
	$player_hand_card_suit		= $_POST["player_hand_card_suit"];
	$player_change_card_no		= $_POST["player_change_card_no"];

	// 山札を再構成
	$stock = $_SESSION["stock"];

	// 山札の残り枚数を取得
	$stock_count = get_stoc_count($stock);

	// 取得したデータからプレーヤーの手札を再構成
	for($i = 0 ; $i < 5 ; $i++){
		$player_hand[$i][$player_hand_card_suit[$i]] = $player_hand_card_number[$i];
	}

	// プレーヤーの挙動に合わせて処理内容を変更する
	switch($_POST["patern"]){
		// 選択した手札を変更する場合
		case "change" :
			// プレーヤーが交換しようとしているカード枚数と山札の残り枚数を確認
			if(count_array($player_change_card_no) <= $stock_count){
				// 変更予定の手札の番号を取得、交換枚数分ループ処理
				foreach($player_change_card_no as $card_no){
					if($card_no != ""){
						// 選択された手札番号のカードを手札から削除
						unset($player_hand[$card_no]);
						// 新しくカードを引いて手札に加える
						$player_hand[$card_no] = draw_card($stock);
					}
				}
				// 手札番号を昇順にソートして並び順を同じにする
				ksort($player_hand);
			}else{
				$err_msg = "山札の枚数が足りなかった為、交換できませんでした。";
			}
			break;

		// 勝負するとき
		case "game" :
			// プレーヤーの役を判定
			$player_hand_no	= judge_hand($player_hand_card_number, $player_hand_card_suit);
			$poker_hand			= $hand_list[$player_hand_no];
	}


	// 全部の処理が終了した時点で現在の山札をセッションに格納、山札の残り枚数を取得
	$stock_count = get_stoc_count($stock);
	$_SESSION["stock"] = $stock;

}else{


	// pre_dump($_GET["verification_hand"]);
	// 検証用（検証用の手札強制操作なので山札は関係なくカードをチョイスしてくる）
	if( isset($_GET["verification_hand"]) ){
		$verification_hand = $_GET["verification_hand"];
		switch($_GET["verification_hand"]){
			// ロイヤルストレートフラッシュ
			case 0 :
				$player_hand = array(0=> array(0=>1), 1=>array(0=>10), 2=>array(0=>11), 3=>array(0=>12), 4=>array(0=>13) );
				break;
			// ストレートフラッシュ
			case 1 :
				$player_hand = array(0=> array(0=>1), 1=>array(0=>2),  2=>array(0=>3),  3=>array(0=>4),  4=>array(0=>5 ) );
				break;
			// フォーカード
			case 2 :
				$player_hand = array(0=> array(0=>1), 1=>array(1=>1),  2=>array(2=>1),  3=>array(3=>1),  4=>array(0=>2 ) );	
				break;
			// フルハウス
			case 3 :
				$player_hand = array(0=> array(0=>1), 1=>array(1=>1),  2=>array(2=>1),  3=>array(0=>13), 4=>array(0=>13) );	
				break;
			// フラッシュ
			case 4 :
				$player_hand = array(0=> array(0=>1), 1=>array(0=>3),  2=>array(0=>5),  3=>array(0=>7),  4=>array(0=>8 ) );	
				break;
			// ストレート
			case 5 :
				$player_hand = array(0=> array(0=>5), 1=>array(1=>2),  2=>array(2=>3),  3=>array(0=>4),  4=>array(1=>1 ) );
				break;
			// スリーカード
			case 6 :
				$player_hand = array(0=> array(0=>1), 1=>array(1=>1),  2=>array(2=>1),  3=>array(0=>3),  4=>array(0=>5 ) );
				break;
			// ツーペア
			case 7 :
				$player_hand = array(0=> array(0=>1), 1=>array(1=>1),  2=>array(2=>2),  3=>array(3=>2),  4=>array(0=>13) );	
				break;
			// ワンペア
			case 8 :
				$player_hand = array(0=> array(0=>1), 1=>array(1=>1),  2=>array(0=>11), 3=>array(0=>12), 4=>array(0=>13) );
				break;
			// ブタ（役なし）
			case 9 :
				$player_hand = array(0=> array(0=>1), 1=>array(0=>2),  2=>array(0=>3),  3=>array(1=>12), 4=>array(1=>13) );	
				break;
		}
	}else{
		// GET送信時は最初の手札を用意する。
		for($i = 0 ; $i < 5 ; $i++){
			$player_hand[$i]	= draw_card($stock);
		}
	}

	// 山札の残り枚数を取得
	$stock_count = get_stoc_count($stock);

	// 山札はプレーヤーに見えないようにセッションに格納
	$_SESSION["stock"] = $stock;

}


// ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝　以降、画面表示　＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta charset="utf-8">
	<title>簡易ポーカー</title>
	<style>

		/* メインコンテンツ（画面全体） */
			.main{
			margin			: auto;
			max-width		: 1200px;
			min-width		: 640px;
			padding			: 30px;
			transition	: 1s;
		}
		.main_fadeout{
			opacity	: 30%;
		}

		/* 役名を表示する領域（画面右っ側に隠してある） */
		.poker_hand {
			display				: inline-block;
			position			: fixed;
			z-index				: 1;
			top						: 10%;
			width					: 800px;
			height				: 150px;
			right					: -900px;
			/* border				: solid 20px yellow; */
			border-radius : 100px;
			background		: #00FFFF;
			text-align		: center;
			padding 		  : 100px 1em 0px 1em ;
			color					: black;
			font-size			: 3em;
			transition		: 1s;
		}
		.poker_hand_fade_in {
			margin			: auto;
			top					: 30%;
			right				: 50%;
			transform		: translateX(50%);
			transition	: 1s;
		}

		/* 検証用の手札強制操作セレクトボックス関連 */
		.verification_hand_content{
			margin			: auto;
			max-width		: 1200px;
			min-width		: 640px;
			height			: 100%;
		}
		.verification_hand_form{
			text-align : right;
		}
		.verification_hand{
			height	: 30px;
			padding	: 0.1em;
		}

		/* エラー表記 */
		.err{
			color : red;
		}

		/* カードを並べるテーブル */
		.game_board{
			height			: 100%;
			border			: outset 10px #CC3300;
			background	: green;
			padding			: 1em;
			color				: white;
		}

		/* 勝負するボタン */
		.game_btn_content{
			position		: relative;
			text-align	: center;
			color				: black;
		}
		.game_btn_string{
			position					: absolute;
			top								: 30%;
			left							: 50%;
			-ms-transform			: translate(-50%,-30%);
			-webkit-transform	: translate(-50%,-30%);
			transform					: translate(-50%,-30%);
			font-size					: 2em;
			font-weight				: bold;
		}
		.game_btn{
			width				: 380px;
			height			: 200px;
			transition	: 0.3s;
		}
		.game_btn:hover{
			width				: 500px;
			/* height			: 230px; */
			transition	: 0.3s;
		}
		.game_string:hover{
			color				: red;
			transition	: 0.3s;
		} 

		/* 手札 */
		.hand_list{
			display					: flex;
			justify-content	: center;
		}
		.card{
			margin				: 5px;
			border				: solid 5px	green;
			border-radius : 10px;
			transition		: 0.2s;
		}
		.card:hover{
			background	: red;
			
  		transform		: translateY(-30px);
			transition	: 0.2s;
		}
		.selected{
			border				: solid 5px red;
			box-shadow		: 0 3px 6px 0 rgba(0, 0, 0, 0.25);
  		transform			: translateY(-30px);
		}
		.card_img{
			width			: 100%;
			height		: 100%;
		}
		.card_img:hover{
			opacity			: 75%;
		}

		/* カードを交換するボタン */
		.change_btn {
			display				: block;
			margin				: auto;
			width					: 300px;
			height				: 80px;
			border-radius : 100px;
			background		: yellow;
			text-align		: center;
			font-size			: 1.2em;
			color					: blue;
			transition		: 0.5s;
		}
		.change_btn:hover {
			color				: red;
			font-size		: 1.4em;
			font-weight	: bold;
			transition	: 0.2s;
		}

		/* 新しいゲームを始めるボタン */
		.new_game_btn_content{
			text-align 		: center;
		}
		.new_game_btn {
			border				: solid 2px #67c5ff;
			border-radius	: 3px;
			padding				: 0.3em 1em;
			background		: white;
			color					: #67c5ff;
			font-size			: 1.5em;
			transition		: 0.4s;
		}
		.new_game_btn:hover {
			background		: #67c5ff;
			color					: white;
		}

		/* フッター */
		.footer{
			margin-top				: 100px;
			padding						: 10px 20px;
			background-color	: #5ab4bd;
			color							: white;
			text-align				: center;
		}
	</style>

	<!-- FontAwesomeの読み込み -->
	<link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet">

	<script type="text/javascript" src="http://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script>
		// 初期設定
		window.onload = function(){
			// 役名がある（勝負したボタン押下後の役判定が終わっている場合）時は役名を表示させる
			var poker_hand = "<?= $poker_hand ?>";
			if(poker_hand != ""){
				var poker_hand_tag	= document.getElementById("poker_hand");
				var main_tag				= document.getElementById("main");
				// フェードインで表示させる
				poker_hand_tag.classList.add('poker_hand_fade_in');
				main_tag.classList.add('main_fadeout');
				// 3 秒後にフェードアウトさせる
				setTimeout(function(){
					$('#poker_hand').fadeOut(1000);
					main_tag.classList.remove('main_fadeout');
				}, 3000);
			}

			// 手札強制操作時に選択した役を選択中にする（PHPでやってもいいけどHTML書き換えるのめんどかったのでJSで処理）
			var verification_hand  = "<?= $verification_hand ?>";
			if(verification_hand != ""){
				var verification_hand_tags	= document.getElementById("verification_hand").children;
				for(var i = 0 ; i < verification_hand_tags.length ; i++){
					if(verification_hand_tags[i].value == verification_hand){
						verification_hand_tags[i].selected = true;
					}
				}
			}
		}

		// 勝負する時
		function game(){
			if( confirm("この手札で勝負しますか？") ){
				// フォームを送信
				var poker_hand_form = document.getElementById("poker_hand_form");
				poker_hand_form.submit();
			}
		}

		// カードを変更する時
		function change(){
			// PHP 側の処理切り分けフラグを変更
			document.getElementById("patern").value = "change";
			// フォームを送信
			var poker_hand_form = document.getElementById("poker_hand_form");
			poker_hand_form.submit();
		}

		// カードを選択した時
		function select_card(card_number){
			console.log("select" + card_number);
			document.getElementById("change_card_" + card_number).value = parseInt(card_number);
			var card_tag = document.getElementById("card_" + card_number);
			card_tag.setAttribute("onclick", "unselect_card(" + card_number + ")");
			card_tag.classList.add("selected");
		}

		// カードを選択から外した時
		function unselect_card(card_number){
			console.log("unselect" + card_number);
			document.getElementById("change_card_" + card_number).value = "";
			var card_tag = document.getElementById("card_" + card_number);
			card_tag.setAttribute("onclick", "select_card(" + card_number + ")");
			card_tag.classList.remove("selected");
		}

		// 新しいゲームを始める時
		function new_game(){
			if( confirm("現在のゲームを中断して新しいゲームを始めますか？") ){
				// 当該ファイルをGET送信
				var new_game_form = document.getElementById("new_game_form");
				new_game_form.submit();
			}
		}

		//「勝負する！」の文字がホバーされた時はギザギザの幅を拡大
		function zoomup(){
			var btn_tag = document.getElementById("game_btn");
			btn_tag.style.width  = "500px";
			var btn_string = document.getElementById("game_btn_string");
			btn_string.style.color  = "red";
			// btn_tag.style.height = "230px";
		}

		//「勝負する！」の文字からマウスが離された時はギザギザの幅を元に戻す
		function zoomdown(){
			var btn_tag = document.getElementById("game_btn");
			btn_tag.style.width  = "400px";
			var btn_string = document.getElementById("game_btn_string");
			btn_string.style.color  = "black";
			// btn_tag.style.height = "230px";
		}

		// 検証用手札で選んだ役番号をGET送信する
		// 検証用の手札強制操作セレクトボックスの内、何れかの役が選択されれば強制発火
		function verification_submit(form_id){
			var verification_hand_form = document.getElementById("verification_hand_form");
			verification_hand_form.submit();
		}
	</script>
</head>
<body>
	<section id="poker_hand" class="poker_hand">
		<?= $poker_hand ?>
	</section>
	<div id="main" class="main">
		<h1>簡易ポーカー</h1>
		<section>
			<!-- <h2>簡易ポーカー</h2> -->
			<section class="err">
				<?= $err_msg ?>
			</section>
			<p>山札が無くなるまで何回でも交換できるヨ。実際のルールとはかなり違うけどまあまあ笑・・・デザインセンスに関してはまあまあ笑・・・</p>
						<!-- 検証用の手札強制操作タグ -->
						<section class="verification_hand_content">
				<form id="verification_hand_form" class="verification_hand_form" action="<?= $source_name ?>" method="GET">
					検証用・手札強制操作<br>（山札を無視して手札をチョイス）<br>
					<select id="verification_hand" class="verification_hand" name="verification_hand" onchange="verification_submit()">
						<option disabled selected >役を選んでください</option>
						<option value="0">ロイヤルストレートフラッシュ</option>
						<option value="1">ストレートフラッシュ</option>
						<option value="2">フォーカード</option>
						<option value="3">フルハウス</option>
						<option value="4">フラッシュ</option>
						<option value="5">ストレート</option>
						<option value="6">スリーカード</option>
						<option value="7">ツーペア</option>
						<option value="8">ワンペア</option>
						<option value="9">ブタ（役なし）</option>
					</select>
				</form>
			</section>
			<br>

			<!-- カードとか表示するテーブルコンテンツ -->
			<section class="game_board">
			山札の残り枚数：<?= $stock_count ?><br>
				<form id="poker_hand_form" action="<?= $source_name ?>" method="POST">
					<!-- 勝負する！のボタン -->
					<div class="game_btn_content">
						<p id="game_btn_string" class="game_btn_string" onclick="game()" onmouseover="zoomup()"  onmouseout="zoomdown()">勝負する！</p>
						<img id="game_btn" class="game_btn" src="./img/game.png" onclick="game()" onmouseover="zoomup()"  onmouseout="zoomdown()">
					</div>
					<br>
					<!-- プレーヤーの手札 -->
					<div class="hand_list">
						<?php
							// ユーザーの手札を取得して表示
							foreach ($player_hand as $card_no => $card_data){
								foreach ($card_data as $suit_no => $card_number){
									echo "<div id=\"card_$card_no\" class=\"card\" onclick=\"select_card($card_no)\">";
									echo "<img class=\"card_img\"src=\"./img/{$suit_no}-{$card_number}.png\"
													alt=\"柄：{$suit[$suit_no]}、数値：{$card_number}\">";
									// カードが選択されていれば、Jsから手札の番号（0～4。カードの数値ではない）を取得して値に入れる
									echo "<input type=\"hidden\" id=\"change_card_$card_no\" name=\"player_change_card_no[]\" >";
									echo "<input type=\"hidden\" name=\"player_hand_card_suit[]\" value=\"$suit_no\"   >";
									echo "<input type=\"hidden\" name=\"player_hand_card_number[]\" value=\"$card_number\" >";
									echo "</div>";
								}
							}
						?>
					</div>
					<br>
					<input type="button" class="change_btn" value="選択したカードを変更する" onclick="change()">
					<input type="hidden" id="patern" name="patern" value="game">
					<br>
				</form>
			</section>
			<br>
			<form id="new_game_form" class="new_game_btn_content" action="<?= $source_name ?>" method="GET">
				<input type="button" class="new_game_btn" value="新しいゲームを始める" onclick="new_game()">
			</form>
		</section>
	</div>

	<!-- footer -->
	<footer class="footer" itemscope itemtype="http://schema.org/Person">
		<p>お問い合わせは
			<a href="mailto:hiroaki.akino@gmail.com?subject=お問い合わせ&amp;body=----------------------------------------%0D%0Aお名前：%0D%0A----------------------------------------%0D%0A 以降にお問い合わせ内容を記載下さい。">
				コチラ
			</a>
		</p>
		<i class="far fa-copyright"></i>
		<small> 2020 <a href="https://www.g096407.shop/hiroaki-akino/self_introduction.html"> Hiroaki Akino</a></small>
	</footer>
</body>
</html>
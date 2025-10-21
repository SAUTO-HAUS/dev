<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Use existing database connection instead of App class
$sql = 'SELECT * FROM '.$prefx.'_car_list ORDER BY `br` ASC, `mo` ASC';
$pdo_stmt = $db->prepare($sql);
$pdo_stmt->execute();
$pdo = $pdo_stmt->fetchAll(PDO::FETCH_ASSOC);
/*
$rtrn .= '<div id="add_new" class="bx" title="'.$lng['adm']['add'].'"> <div></div> </div>';
$br_l = ''; $br_l_ar = []; $mo_l = ''; $ttl = '';
foreach ($pdo as $r){
    if ( $br_l != substr($r['br'], 0, 1) ){ $br_l = substr($r['br'], 0, 1); $br_l_ar[] = $br_l; $rtrn .= '<a name="br_l_lst_'.$br_l.'" style="width:97%; display:block; margin:1rem 0; text-align:right; color:var(--clr); font-size:2rem; text-transform:uppercase;">'.$br_l.'</a>'; }
    if ( $ttl != $r['br_nm'] ){ $ttl = $r['br_nm']; $rtrn .= '<div style="width:100%; margin:1rem 0; text-align:center; border-top:1px solid #292929; font-size:1.1rem; color:var(--clr); padding-top:1rem;">'.$r['br_nm'].'</div>'; }
    if ( $mo_l!=substr($r['mo'], 0, 1) ){$mo_l = substr($r['mo'], 0, 1); $rtrn .= '<div style="width:100%; text-transform:uppercase; text-align:center; font-size:.7rem; border-top:1px dashed #ddd; margin-top:1rem;">'.$mo_l.'</div>';}
    $rtrn .= '<span style="color:#ccc; font-size:.7rem;">'.$r['br_nm'].'</span> <span>'.$r['mo_nm'].'</span><br/>';
}

$rtrn .=
'<div id="az_bx" style="position:fixed; right:0; top:10%; display:flex; flex-flow:column wrap; text-transform:uppercase; background-color:#fff9; padding:1rem; text-align:center;">';
    $az_ar = range('a', 'z');

    foreach($az_ar as $v){
        $rtrn .= ( in_array($v, $br_l_ar)?'<a href="#br_l_lst_'.$v.'">'.$v.'</a>':'<a class="ghost" style="color:#d5d5d5;">'.$v.'</a>' );
    }
$rtrn .=
'</div>';
*/

$br_mo_ar = [];
foreach ($pdo as $r){
    if ( !isset($br_mo_ar[ $r['br'] ]) ){ $br_mo_ar[ $r['br'] ]['br_nm'] = $r['br_nm']; }
    $br_mo_ar[ $r['br'] ]['mo_ar'][ $r['mo'] ] = $r['mo_nm'];
}
?>

<style>
    .pg_ttl {display:inline-block; border-bottom:2px solid; margin-bottom:2rem; padding:0 5rem 0 1rem;}

    #br_mo {display:flex; flex-flow:row wrap;}
    #br_mo > .bx {width:14rem;}
    #br_mo > .bx > .ttl {text-align:center;}
    #br_mo > .bx > .lst {width:100%; height:20rem; overflow-y:scroll; padding:0 1rem; border-top:1px solid var(--clr); border-bottom:1px solid #eee; margin-top:.5rem;}
    #br_mo > .bx > .lst .el {width:100%; height:1rem; margin:.5rem 0; cursor:pointer; transition:.2s color;}
    #br_mo > .bx > .lst .el.act,
    #br_mo > .bx > .lst .el:hover {color:var(--clr);}
    #br_mo > .bx > .lst .el.act:before {content:"> ";}

    #br_mo > .bx > .btn.add_new {text-align:center; background-color:#eee; margin:1rem 2rem 0; padding:.5rem 1rem; cursor:pointer; transition:.3s;}
    #br_mo > .bx > .btn.add_new:hover {background-color:var(--clr); color:#fff;}
</style>
		
<script>
    $(document).ready(function(){
        $("#br_mo > .bx > .lst .el").on("click", function(e){
            if ( !$(this).hasClass("act") && e.target === this ){
                $(this).closest(".lst").find(".el.act").removeClass("act");
                $(this).addClass("act");

                if ( $(this).closest(".bx").hasClass("br") ){
                    $("#br_mo > .bx.mo > .lst > .gr").addClass("none");
                    $("#br_mo > .bx.mo > .lst > .gr[data-par=\""+$(this).data("br")+"\"]").removeClass("none").children(".el.act").removeClass("act");
                    $("#br_mo").data({"br":$(this).data("br"), "br_nm":$(this).data("br_nm")}).attr({"data-br":$(this).data("br"), "data-br_nm":$(this).data("br_nm")}).removeAttr("data-mo data-mo_nm")
                }
                else if ( $(this).closest(".bx").hasClass("mo") ){
                    $("#br_mo").data({"mo":$(this).data("mo"), "mo_nm":$(this).data("mo_nm")}).attr({"data-mo":$(this).data("mo"), "data-mo_nm":$(this).data("mo_nm")})
                }
            } else {
                return;
            }
        })
        var zzzz = $("#br_mo > .ttlz").text();
        console.log(zzzz)
        console.log( zzzz.toLowerCase().replace(/\ |\-|\//g,"_").replace(/\&/g,"_and_").replace(/\!/g,"I").replace(/\+/g,"_plus_").replace(/\_\_\_|\_\_/g,"_").replace(/^\_|\_$/g,"") )
    })
</script>
		
<div class="pg_ttl">ELEMENT LIST EDITOR</div>
<div id="br_mo">
    <div class="ttlz none">Cadillac ESV/EXT</div>
    <div class="bx br">
        <div class="ttl">Brand</div>
        <div class="lst">
            <?php foreach( $br_mo_ar as $br => $ar ) : ?>
                <div class="el" data-br="<?= $br ?>" data-br_nm="<?= $ar['br_nm'] ?>"> <?= $ar['br_nm'] ?></div>
            <?php endforeach; ?>
        </div>
        <div class="btn add_new">New</div>
    </div>
    <div class="bx mo">
        <div class="ttl">Model</div>
        <div class="lst">
            <?php foreach( $br_mo_ar as $br => $ar) : ?>
                <div class="gr none" data-par="<?= $br ?>">
                    <?php foreach ( $ar['mo_ar'] as $mo => $mo_nm ) : ?>
                        <div class="el" data-mo="<?= $mo ?>" data-mo_nm="<?= $mo_nm ?>"><?=$mo_nm?></div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="btn add_new">New</div>
    </div>
    <div class="bx yr">
        <div class="ttl">Year</div>
        <div class="lst"></div>
        <div class="btn add_new">New</div>
    </div>
    <div class="bx cnfg">
        <div class="ttl">Config</div>
        <div class="lst"></div>
        <div class="btn add_new">New</div>
    </div>
    <div class="bx ngn">
        <div class="ttl">Engine</div>
        <div class="lst"></div>
        <div class="btn add_new">New</div>
    </div>
</div>

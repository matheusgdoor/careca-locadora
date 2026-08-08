<?php
declare(strict_types=1);
$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");
function p(string $r,string $x):string{return $r.DIRECTORY_SEPARATOR.str_replace(['/', '\\'],DIRECTORY_SEPARATOR,$x);}
function rr(string $x):string{if(!is_file($x)){fwrite(STDERR,"[ERRO] Arquivo não encontrado: $x\n");exit(2);} $c=file_get_contents($x); if($c===false){exit(3);} return $c;}
function ww(string $x,string $c):void{if(file_put_contents($x,$c)===false){exit(4);}}

echo "\nCareca Locadora - Correção Filial e Navegação 17.5.1\n\n";
$path=p($root,'resources/js/pages/welcome.tsx');
$s=rr($path);

$patterns=[
'~<option(?P<a>[^>]*value=\{branch\.id\}[^>]*)>.*?</option>~s',
'~<option(?P<a>[^>]*value=\{String\(branch\.id\)\}[^>]*)>.*?</option>~s',
];
$done=false;
foreach($patterns as $pat){
    $u=preg_replace_callback($pat,fn($m)=>'<option'.$m['a'].'>{branch.name}</option>',$s,-1,$n);
    if($u!==null && $n>0){$s=$u;$done=true;break;}
}
foreach([
'{branch.name} — {branch.city}/{branch.state}'=>'{branch.name}',
'{branch.name} · {branch.city}/{branch.state}'=>'{branch.name}',
'{branch.label}'=>'{branch.name}',
'{branch.display_name}'=>'{branch.name}',
] as $a=>$b){ if(str_contains($s,$a)){ $s=str_replace($a,$b,$s); $done=true; } }

if(!$done && !str_contains($s,'>{branch.name}</option>')){
    fwrite(STDERR,"[ERRO] Option de filial não localizado.\n"); exit(5);
}

$s=str_replace('Loja de retirada','Filial',$s);
$s=str_replace('Todas as lojas','Todas as filiais',$s);

foreach([
'~\s*<a\b[^>]*>\s*Lojas\s*</a>~s',
'~\s*<Link\b[^>]*>\s*Lojas\s*</Link>~s'
] as $pat){$s=preg_replace($pat,'',$s)??$s;}

foreach([
'~<a\b[^>]*href=["\']/vantagens["\'][^>]*>\s*Vantagens\s*</a>~s',
'~<a\b[^>]*href=["\']/filiais["\'][^>]*>\s*Filiais\s*</a>~s'
] as $pat){
    preg_match_all($pat,$s,$m);
    if(count($m[0]??[])>1){
        $first=true;
        $s=preg_replace_callback($pat,function($m)use(&$first){if($first){$first=false;return $m[0];}return '';},$s)??$s;
    }
}
ww($path,$s);

$s=rr($path);
foreach(['Filial','Todas as filiais','{branch.name}'] as $n){
    if(!str_contains($s,$n)){fwrite(STDERR,"[ERRO] Validação falhou: $n\n");exit(10);}
}
if(preg_match('~>\s*Lojas\s*<~s',$s)===1){fwrite(STDERR,"[ERRO] Lojas ainda presente.\n");exit(11);}
echo "[OK] Filtro usa somente Nome da filial.\n";
echo "[OK] Menu sem item Lojas legado e sem duplicidades institucionais.\n";
echo "\n[OK] 17.5.1 aplicada com sucesso.\n";

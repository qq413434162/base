<li class="
[field:pubdate runphp='yes']  
$a="new"; 
$b="";    
$c=strftime("%d","@me");
$d=date(d); 
if($c==$d) @me = $a;  
else @me = $b;  
[/field:pubdate]   
   ">
            <span class="date">[field:pubdate runphp='yes']  
            $a="日"; 
            $b="今日";    
            $c=strftime("%Y%m%d","@me");
            $d=date(Ymd); 
            $e=strftime("%d","@me");
            if($c==$d) @me = $b;  
            else @me = $e.$a;  
            [/field:pubdate]</span><span class="title"><a title="[field:fulltitle/]" target="_blank" href="[field:arcurl/]">[field:shorttitle/]</a></span>
            </li>
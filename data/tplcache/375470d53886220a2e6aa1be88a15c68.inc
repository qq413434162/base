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
      $a="»’"; 
      $b="ΩÒ»’";    
      $c=strftime("%Y%m%d","@me");
      $d=date(Ymd); 
      $e=strftime("%d","@me");
      if($c==$d) @me = $b;  
      else @me = $e.$a;  
      [/field:pubdate]</span><span class="title"><a title="[field:fulltitle/]" target="_blank" href="[field:global.cfg_basehost/][field:arcurl/]">[field:shorttitle/]</a></span>
      </li>
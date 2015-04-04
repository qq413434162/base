<div class="listbox">
    <h2><span class="block_cate"><a target="_blank" href="{dede:global.cfg_basehost/}{dede:field name='typeurl'/}">{dede:field name='typename'/}</a></span><span class="more"><a target="_blank" href="{dede:global.cfg_basehost/}{dede:field name='typeurl'/}">更多...</a></span></h2>
    <ul>
      {dede:arclist row='12' titlelen=60}
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
      [/field:pubdate]</span><span class="title"><a title="[field:fulltitle/]" target="_blank" href="[field:global.cfg_basehost/][field:arcurl/]">[field:shorttitle/]</a></span>
      </li>
      {/dede:arclist}
    </ul>
  </div>
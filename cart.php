<?php $title='Giỏ hàng'; require 'header.php';
$lines=cart_lines($pdo); $tot=cart_totals($lines); $logged=is_customer(); $cm=(string)($_SESSION['cart_msg']??''); unset($_SESSION['cart_msg']); ?>
<?= steps(1) ?>
<?php if($cm) echo '<div class="msg err">'.e($cm).'</div>'; ?>
<div class="cart2">
<div>
<div class="panel chead"><label class="ck"><input type="checkbox" id="all" checked><i></i></label><b class="ct">Tất cả sản phẩm (<span id="cn"><?= count($lines) ?></span>)</b><span class="hc">Đơn giá</span><span class="hc">Số lượng</span><button type="button" class="clr" id="clr" title="Xóa tất cả sản phẩm">Xóa tất cả</button></div>
<div class="panel" id="clist"></div>
<p class="cnext"><a class="btn ghost" href="products.php">← Tiếp tục mua sắm</a><button class="btn red" id="cnext" type="button">Tiếp theo <?= ico('arrow') ?></button></p>
</div>
<div><?= sum_panel($tot) ?><p class="mut" style="font-size:12.5px;margin:10px 4px">Phí vận chuyển và các yêu cầu đặc biệt được chọn ở bước tiếp theo.</p></div>
</div>
<?php if(!$logged): ?>
<div class="mback" id="mback" hidden><div class="mbox" role="dialog" aria-modal="true" aria-labelledby="mt"><button class="mx" id="mx" type="button" aria-label="Đóng">✕</button>
<div class="mshield"><?= ico('shield') ?></div><h3 id="mt">Đăng nhập để tiếp tục đặt hàng</h3><p class="mut">Giỏ hàng vẫn được giữ nguyên. Sau khi đăng nhập bạn sẽ được chuyển sang bước nhập thông tin giao hàng.</p>
<form method="post" action="login.php"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="next" value="checkout.php">
<input name="u" placeholder="Số điện thoại hoặc email" autocomplete="username" required><?= pw_field('mp','p','Mật khẩu','current-password') ?>
<div class="arow"><span></span><a class="alink" href="forgot.php">Quên mật khẩu?</a></div>
<button class="abtn" type="submit" style="margin-top:0">Đăng nhập</button></form><?= google_button('checkout.php') ?>
<p class="afine">Chưa có tài khoản? <a class="alink" href="register.php?next=checkout.php">Đăng ký ngay</a></p>
<button class="mlink" id="mback2" type="button">Quay lại giỏ hàng</button></div></div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',function(){
var Z=window.ZCart,S={},items=[],logged=<?= $logged?'true':'false' ?>;
var list=document.getElementById('clist'),all=document.getElementById('all'),nx=document.getElementById('cnext');
function $(i){return document.getElementById(i)}
function ok(i){return i.stock>0}
function draw(){
 $('cn').textContent=items.length;
 if(!items.length){list.innerHTML='<div class="cempty2"><p>Giỏ hàng của bạn đang trống.</p><a class="btn red" href="products.php">Mua sắm ngay</a></div>';nx.disabled=true;all.checked=false;totals();return}
 list.innerHTML=items.map(function(it){var oos=!ok(it),on=!oos&&S[it.id]!==false,note=oos?' · <b class="stk out">Hết hàng, vui lòng xóa khỏi giỏ</b>':(it.stock<=5?' · <b class="stk low">Chỉ còn '+it.stock+'</b>':'');
  return '<div class="crow'+(oos?' oosrow':'')+'" data-id="'+it.id+'"><label class="ck"><input type="checkbox" class="sel" '+(on?'checked':'')+(oos?' disabled':'')+'><i></i></label><a class="cth" href="'+it.url+'">'+it.thumb+'</a><div class="cinfo"><a href="'+it.url+'">'+Z.esc(it.name)+'</a><small>'+Z.esc(it.brand)+' · Bảo hành chính hãng'+note+'</small></div><div class="cprice">'+(it.old>it.price?'<s>'+Z.f(it.old)+'</s>':'')+'<b>'+Z.f(it.price)+'</b></div><div class="qty"><button type="button" class="qm" aria-label="Giảm"'+(oos?' disabled':'')+'>−</button><span>'+it.qty+'</span><button type="button" class="qp" aria-label="Tăng"'+((oos||it.qty>=it.stock)?' disabled':'')+'>+</button></div><button type="button" class="cdel" aria-label="Xóa sản phẩm">'+Z.TR+'</button></div>'}).join('');
 totals()}
function sel(){return items.filter(function(i){return ok(i)&&S[i.id]!==false})}
function totals(){var s=sel(),l=0,p=0,pool=items.filter(ok);s.forEach(function(i){l+=Math.max(i.old,i.price)*i.qty;p+=i.price*i.qty});
 $('s_list').textContent=Z.f(l);$('s_disc').textContent='-'+Z.f(l-p);$('s_sub').textContent=Z.f(p);$('s_total').textContent=Z.f(p);
 var sv=$('s_save');sv.textContent='Tiết kiệm '+Z.f(l-p);sv.hidden=(l-p)<=0;
 all.checked=pool.length>0&&s.length===pool.length;nx.disabled=s.length===0}
function apply(d){if(d&&d.ok){items=d.items;draw()}}
Z.post({act:'get'}).then(apply);
all.onchange=function(){items.forEach(function(i){S[i.id]=all.checked&&ok(i)});draw()};
$('clr').onclick=function(){if(items.length&&confirm('Xóa tất cả sản phẩm trong giỏ hàng?'))Z.post({act:'clear'}).then(apply)};
list.addEventListener('change',function(e){if(e.target.classList.contains('sel')){S[e.target.closest('.crow').getAttribute('data-id')]=e.target.checked;totals()}});
list.addEventListener('click',function(e){var r=e.target.closest('.crow');if(!r)return;var id=r.getAttribute('data-id'),q=parseInt(r.querySelector('.qty span').textContent,10);
 if(e.target.closest('.qm'))Z.post({act:'set',id:id,qty:q-1}).then(apply);
 else if(e.target.closest('.qp'))Z.post({act:'set',id:id,qty:q+1}).then(apply);
 else if(e.target.closest('.cdel'))Z.post({act:'del',id:id}).then(apply)});
var mb=$('mback');function mclose(){if(mb)mb.hidden=true}
if(mb){$('mx').onclick=mclose;$('mback2').onclick=mclose;mb.addEventListener('click',function(e){if(e.target===mb)mclose()});document.addEventListener('keydown',function(e){if(e.key==='Escape')mclose()})}
nx.onclick=function(){var ids=sel().map(function(i){return i.id});if(!ids.length)return;
 Z.post({act:'sel',ids:ids.join(',')}).then(function(d){if(!d||!d.ok)return;if(logged)location.href='checkout.php';else if(mb){mb.hidden=false;var f=mb.querySelector('input[name=u]');if(f)f.focus()}else location.href='login.php?next=checkout.php'})};
});
</script>
<?php require 'footer.php'; ?>

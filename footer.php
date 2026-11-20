<?php if(!empty($bare)){ echo '</body></html>'; return; } ?>
</main>
<footer><div class="fin">
<div><?= shop_logo() ?><p style="margin-top:12px">Laptop, PC, màn hình, linh kiện và phụ kiện chính hãng.</p></div>
<div><h4>Hỗ trợ khách hàng</h4><ul><li>Bảo hành chính hãng 12–36 tháng</li><li>1 đổi 1 trong 30 ngày đầu</li><li>Giao hàng toàn quốc</li><li>Trả góp 0% qua thẻ tín dụng</li></ul></div>
<div><h4>Về chúng tôi</h4><ul><li><a href="products.php">Sản phẩm</a></li><li><a href="cart.php">Giỏ hàng</a></li><li><a href="prices.php">Bảng giá</a></li><li><a href="login.php">Quản trị</a></li></ul></div>
<div><h4>Liên hệ</h4><ul><li>Hotline: <b><?= e(SHOP_PHONE) ?></b></li><li>Email: <?= e(SHOP_EMAIL) ?></li><li>Mở cửa <?= e(SHOP_HOURS) ?></li></ul></div></div>
<div class="copy"><span>© 2026 <?= e(SHOP_NAME) ?>. Đồ án 2 (ĐA2-TH027): Website bán máy tính trực tuyến.</span></div></footer>
<div class="cov" id="cov"></div>
<aside class="cdrawer" id="cdrawer" aria-hidden="true" aria-label="Giỏ hàng của bạn"><div class="cdh"><h3>Giỏ hàng của bạn</h3><button type="button" id="cclose" aria-label="Đóng">✕</button></div><div class="cdb" id="cdbody"></div><div class="cdf"><a class="cbig" id="cview" href="cart.php">Xem giỏ hàng</a></div></aside>
<script src="cart.js?v=<?= (int)@filemtime(__DIR__.'/cart.js') ?>"></script>
<script>
(function(){
var s=document.querySelector('.slider');
if(s){var sl=s.querySelectorAll('.slide'),ds=s.querySelectorAll('.dots button'),i=0,t;
var go=function(n){sl[i].classList.remove('on');if(ds[i])ds[i].classList.remove('on');i=(n+sl.length)%sl.length;sl[i].classList.add('on');if(ds[i])ds[i].classList.add('on')};
var auto=function(){clearInterval(t);if(sl.length>1)t=setInterval(function(){go(i+1)},5500)};
for(var k=0;k<ds.length;k++){(function(k){ds[k].onclick=function(){go(k);auto()}})(k)}
var pv=s.querySelector('.prev'),nx=s.querySelector('.next');
if(pv)pv.onclick=function(){go(i-1);auto()};if(nx)nx.onclick=function(){go(i+1);auto()};auto();}
var pl=document.querySelectorAll('.pills button');
for(var j=0;j<pl.length;j++){pl[j].onclick=function(){var c=this.getAttribute('data-cat'),n=0;for(var q=0;q<pl.length;q++)pl[q].classList.remove('on');this.classList.add('on');document.querySelectorAll('#fgrid .card').forEach(function(x){var ok=c==='all'||x.getAttribute('data-cat')===c;x.style.display=ok?'':'none';if(ok)n++});var em=document.getElementById('fempty');if(em)em.hidden=n>0}}
document.addEventListener('click',function(e){var c=document.getElementById('catbox');if(c&&!c.contains(e.target))c.classList.remove('open')});
})();
</script></body></html>

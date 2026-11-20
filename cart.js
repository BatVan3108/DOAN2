(function(){
var M=document.querySelector('meta[name=csrf]'),token=M?M.content:'';
function esc(s){return String(s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function f(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'.')+'đ'}
function toast(m){var t=document.createElement('div');t.className='toast';t.setAttribute('role','status');t.textContent=m;document.body.appendChild(t);setTimeout(function(){t.classList.add('out')},2600);setTimeout(function(){t.remove()},3100)}
function post(o){var b=new URLSearchParams();b.append('t',token);for(var k in o)b.append(k,o[k]);
 return fetch('cart_api.php',{method:'POST',body:b,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(d){if(d&&d.msg)toast(d.msg);if(d&&d.ok)setBadge(d.count,o.act!=='add');return d}).catch(function(){toast('Không kết nối được máy chủ.');return{ok:false}})}
var badge=document.getElementById('cartcount');
function setBadge(n,quiet){if(!badge)return;badge.textContent=n;badge.classList.toggle('zero',n<1);if(!quiet){badge.classList.remove('bump');void badge.offsetWidth;badge.classList.add('bump')}}
var dr=document.getElementById('cdrawer'),ov=document.getElementById('cov'),body=document.getElementById('cdbody'),view=document.getElementById('cview');
var TR='<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>';
function render(d){if(!body)return;var tot=0;
 if(!d.items.length){body.innerHTML='<p class="cempty">Giỏ hàng của bạn đang trống.</p>';view.textContent='Xem giỏ hàng';return}
 body.innerHTML=d.items.map(function(it){var oos=it.stock<=0;if(!oos)tot+=it.price*it.qty;
  return '<div class="ditem" data-id="'+it.id+'"><a class="dth" href="'+it.url+'">'+it.thumb+'</a><div class="dinf"><a class="dn" href="'+it.url+'">'+esc(it.name)+'</a><small>'+esc(it.brand)+(oos?' · <b class="stk out">Hết hàng</b>':(it.stock<=5?' · <b class="stk low">Còn '+it.stock+'</b>':''))+'</small>'+(it.old>it.price?'<s>'+f(it.old)+'</s>':'')+'<div class="drow"><b>'+f(it.price)+'</b><span class="qty"><button class="dqm" type="button" aria-label="Giảm">−</button><span>'+it.qty+'</span><button class="dqp" type="button" aria-label="Tăng"'+((oos||it.qty>=it.stock)?' disabled':'')+'>+</button></span></div></div><button class="ddel" type="button" aria-label="Xóa">'+TR+'</button></div>'}).join('');
 view.textContent='Xem giỏ hàng — '+f(tot)}
function open(){if(!dr)return;dr.classList.add('open');ov.classList.add('open');dr.setAttribute('aria-hidden','false');document.body.classList.add('noscroll')}
function close(){if(!dr)return;dr.classList.remove('open');ov.classList.remove('open');dr.setAttribute('aria-hidden','true');document.body.classList.remove('noscroll')}
function show(d){if(d&&d.ok){render(d);open()}}
document.addEventListener('click',function(e){var t=e.target;
 var add=t.closest('.cadd'),buy=t.closest('.cbuy');
 if(add){e.preventDefault();e.stopPropagation();post({act:'add',id:add.getAttribute('data-id')}).then(show);return}
 if(buy){e.preventDefault();post({act:'add',id:buy.getAttribute('data-id')}).then(function(d){if(d&&d.ok)location.href='cart.php'});return}
 if(t.closest('#cartbtn')){e.preventDefault();post({act:'get'}).then(show);return}
 if(t.closest('#cclose')||t===ov){close();return}
 var it=t.closest('.ditem');if(!it||!dr||!dr.contains(it))return;var id=it.getAttribute('data-id'),q=parseInt(it.querySelector('.qty span').textContent,10);
 if(t.closest('.dqm'))post({act:'set',id:id,qty:q-1}).then(function(d){if(d.ok)render(d)});
 else if(t.closest('.dqp'))post({act:'set',id:id,qty:q+1}).then(function(d){if(d.ok)render(d)});
 else if(t.closest('.ddel'))post({act:'del',id:id}).then(function(d){if(d.ok)render(d)})});
document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});
window.ZCart={post:post,f:f,esc:esc,setBadge:setBadge,toast:toast,TR:TR};
})();

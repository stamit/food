/* 
*  Copyright 2008 Dynamic Site Solutions.
*  Free use of this script is permitted for commercial and non-commercial 
*  applications, subject to the requirement that this comment block be kept 
*  and not be altered.  The data and executable parts of the script may be 
*  changed as needed.  Dynamic Site Solutions makes no warranty regarding 
*  fitness of use or correct function of the script.  If you would like help
*  customizing this script or if you have other questions, contact 
*  "contact_us@dynamicsitesolutions.com".
*
*  Script by: Dynamic Site Solutions -- http://www.dynamicsitesolutions.com/
*  Last Updated: 2008-01-22
*/

if(!''.camelize) // based on the function from 
{                // http://dhtmlkitchen.com/learn/js/setstyle/index4.jsp
  String.prototype.camelize=function(){
    var s=this,x=/-([a-z])/;
    while(x.test(s)) s=s.replace(x,RegExp.$1.toUpperCase());
    return s;
  }
}

function getStyle(el,prop,numOnly){
  var val='',d=document,c,dV=d.defaultView||window;
  el=(typeof(el)=='string')?d.getElementById(el):el;
  if(dV && dV.getComputedStyle){
    val=dV.getComputedStyle(el,null).getPropertyValue(prop);
  } else {
    prop=(prop=='float')?'cssFloat':prop.camelize();
    if(el.currentStyle){
      val=el.currentStyle[((prop=='cssFloat')?'styleFloat':prop)];
      // From the awesome hack by Dean Edwards
      // http://erik.eae.net/archives/2007/07/27/18.54.15/#comment-102291
      // If we're not dealing with a regular pixel number
      // but a number that has a weird ending, we need to convert it to pixels
      if(!/^\d+(px)?$/i.test(val) && /^\d/.test(val)){
        var style=el.style.left,runtimeStyle=el.runtimeStyle.left;
        el.runtimeStyle.left=el.currentStyle.left,el.style.left=val||0;
        val=el.style.pixelLeft+"px";
        el.style.left=style,el.runtimeStyle.left=runtimeStyle;
      } else if((val=='auto') && (typeof(hasLayoutToggle)=='function')){
        hasLayoutToggle(el,1);
        if(prop=='width') val=(el.clientWidth-getStyle(el,'paddingLeft',1)-
          getStyle(el,'paddingRight',1))+'px';
        else if(prop=='height') val=(el.clientHeight-
          getStyle(el,'paddingTop',1)-getStyle(el,'paddingBottom',1))+'px';
        hasLayoutToggle(el,0);
      }
    } else if(el.style && el.style[prop]) val=el.style[prop];
  }
  if(numOnly) val=parseFloat(val)||0;
  else if((val.indexOf("rgb(")==0)||((val.charAt(0)=='#')&&(val.length==4)))
    // this converts colors to long-hex notation
    if(typeof(Color)=='function') val=(new Color()).set(val).getHex();
  return val;
}
/*

Known issues:

#  A way to check the alpha filter in IE/Win when "opacity" is passed as prop 
   hasn't been implemented.

#  IE doesn't convert named colors to RGB. It's best to avoid using named 
   colors anyway.

#  It seems that in Safari 1.3-2.x getComputedStyle() reports some incorrect 
   values when an element or its ancestor has display:none. I'm not going to 
   bother to implement a fix for that right now.

#  getComputedStyle() is not supported in Safari 1.2 but is in Safari 1.3+.

#  It seems a little buggy in Opera 7.0-7.1x, but seems fine in Opera 7.2+.

*/

function hasLayoutToggle(el,on){ // for IE5+/Win
  if(!el||!el.currentStyle) return;
  /*@cc_on @if(@_jscript&&!(@_win32||@_win16)&&
  (@_jscript_version<5.5)) return; @end @*/  // if IE/Mac return
  if(typeof(el.currentStyle.hasLayout)!='undefined'){
    if(on && el.currentStyle.hasLayout) return;
    el.style.zoom=!on?'':'1';
  } else {
    var re=/^auto|0$/,cs=el.currentStyle;
    if(on && (!re.test(cs.height) || !re.test(cs.width))) return;
    if(on) el._oldHeight=el.style.height;
    el.style.height=!on?(el._oldHeight||''):'1px';
    if(!on) el._oldHeight='';
  }
}

Object.extend=function(dest,src){
  for(var prop in src) dest[prop]=src[prop];
  return dest;
}
// by Kravvitz <http://www.dynamicsitesolutions.com/>
// based on a function by Peter Wilkinson <http://www.dynamic-tools.net/>
function Color(){
  this.r=0;
  this.g=0;
  this.b=0;
}
Object.extend(Color.prototype,{
  set:function(color){
    return (color.indexOf("rgb(")==0)?this.setRGB(color):this.setHex(color);
  },
  setHex:function(h){
    if(h.charAt(0)=="#") h=h.slice(1);
    if(h.length==3){
      var ar=h.split(''),i=-1;
      while(++i<3) ar[i]+=ar[i];
      h=ar.join('');
    }
    this._setValue('r',parseInt(h.slice(0,2),16));
    this._setValue('g',parseInt(h.slice(2,4),16));
    return this._setValue('b',parseInt(h.slice(4,6),16));
  },
  setRGB:function(rgb){
    var colors=rgb.slice(4).split(',');
    this._setValue('r',parseInt(colors[0],10));
    this._setValue('g',parseInt(colors[1],10));
    return this._setValue('b',parseInt(colors[2],10));
  },
  changeColor:function(amount){
    this.changeRed(amount);
    this.changeGreen(amount);
    return this.changeBlue(amount);
  },
  changeRed:function(amount){
    return this._setValue('r',this.r+parseInt(amount));
  },
  changeGreen:function(amount){
    return this._setValue('g',this.g+parseInt(amount));
  },
  changeBlue:function(amount){
    return this._setValue('b',this.b+parseInt(amount));
  },
  getRGB:function(){
    return 'rgb('+this.r+', '+this.g+', '+this.b+')';
  },
  getHex:function(){
    return ('#'+this._toHex(this.r)+this._toHex(this.g)+this._toHex(this.b));
  },
  _toHex:function(n){
    return ((n.toString(16).length==1?'0':'')+n.toString(16).toLowerCase());
  },
  _setValue:function(color,num){
    this[color]=((num<0)?0:((num>255)?255:num));
    return this; // returns a reference to the current Color object
  }
});


////////////////////////////////////////////////////////////////////////////////


function style(el,name,newvalue) {
	el = (typeof el == 'string') ? elem(el) : el;
	var oldvalue = getStyle(el,name);

	if (newvalue !== undefined) {
    		if (el.currentStyle){
			el.currentStyle[name] = newvalue;
		} else {
			el.style[name] = newvalue;
		}
	}

	return oldvalue;
}

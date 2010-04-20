
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

var ilOverlayFunc = function() {
};
ilOverlayFunc.prototype =
{
	overlays: {},
	cfg: {},
	widthFixed: {},
	trigger: {},
	closeCnt: {},
	toggle: {},
	waitMouseOut: 10,
	waitAfterClicked: 20,
	
	add: function (id, cfg)
	{
		ilOverlayFunc.prototype.overlays[id] =
			new YAHOO.widget.Overlay(id, cfg.yuicfg);
		ilOverlayFunc.prototype.cfg[id] = cfg;
		ilOverlayFunc.prototype.closeCnt[id] = -1;
		YAHOO.util.Event.addListener(id, "mouseover",
			function(e) {ilOverlay.mouseOver(e, id);});
		YAHOO.util.Event.addListener(id, "mouseout",
			function(e) {ilOverlay.mouseOut(e, id);});
		
		if (cfg.trigger)
		{
			this.addTrigger(cfg.trigger, cfg.trigger_event, id, cfg.anchor_id);
			//YAHOO.util.Event.addListener(trigger, "click",
			//	function(event) {ilOverlay.toggle(event, id); return false;});
		}
		ilOverlayFunc.prototype.overlays[id].render();
		this.fixPosition(id);
	},
	
	addTrigger: function (tr_id, tr_ev, ov_id, anchor_id)
	{
		ilOverlayFunc.prototype.trigger[tr_id] = {trigger_event: tr_ev, overlay_id: ov_id, anchor_id: anchor_id};
		var trigger = document.getElementById(tr_id);
		YAHOO.util.Event.addListener(trigger, "click",
			function(event) {ilOverlay.togglePerTrigger(event, tr_id); return false;});
	},
	
	getCfg: function (id, name)
	{
		if (this.cfg[id] == null || typeof(this.cfg[id]) == 'undefined')
		{
			return null;
		}
		if (typeof(this.cfg[id][name]) == 'undefined')
		{
			return null;
		}
		return this.cfg[id][name];
	},

	// toggle overlay by trigger elements (often anchor)
	togglePerTrigger: function (e, tr_id)
	{
		var ov_id = ilOverlayFunc.prototype.trigger[tr_id].overlay_id;
		var anchor_id = ilOverlayFunc.prototype.trigger[tr_id].anchor_id;
		this.toggle(e, ov_id, anchor_id)
	},
	
	// toggle overlay	
	toggle: function (e, id, anchor_id)
	{
//console.log("Toggle: " + id + ", Anchor: " + anchor_id);
		if (ilOverlayFunc.prototype.overlays[id].cfg.getProperty('visible'))
		{
			this.hide(e, id);
		}
		else
		{
			this.show(e, id, anchor_id);
		}
	},

	// hide overlay	
	hide: function(e, id)
	{
		this.overlays[id].hide();
		if (e != null)
		{
			YAHOO.util.Event.preventDefault(e);
		}
		this.closeCnt[id] = -1;

		var toggle_el = this.getCfg(id, 'toggle_el');
		var toggle_class_on = this.getCfg(id, 'toggle_class_on');
		if (toggle_el != null && toggle_class_on != null)
		{
			toggle_obj = document.getElementById(toggle_el);
			if (toggle_obj && this.toggle[toggle_el])
			{
				toggle_obj.className = this.toggle[toggle_el];
			}
		}
	},
	
	// show the overlay
	show: function(e, id, anchor_id)
	{
		// hide all other overlays (currently the standard procedure)
		ilOverlay.hideAllOverlays(e);
		
		// display the overlay at the anchor position
		var el = document.getElementById(id);
		el.style.display = '';
		this.overlays[id].cfg.setProperty("context", [anchor_id, "tl", "bl"]);
		this.overlays[id].show();
		this.fixPosition(id);

		// invoke close process (if only the anchor is clicked,
		// the overlay will be hidden after some time, mouseover on the overlay will prevent this)
		this.closeCnt[id] = this.waitAfterClicked;
		this.closeProcess(id);

		// should an additional element be toggled (style class)
		var toggle_el = this.getCfg(id, 'toggle_el');
		var toggle_class_on = this.getCfg(id, 'toggle_class_on');
		if (toggle_el != null && toggle_class_on != null)
		{
			toggle_obj = document.getElementById(toggle_el);
			if (toggle_obj)
			{
				this.toggle[toggle_el] = toggle_obj.className;
				toggle_obj.className = toggle_class_on;
			}
		}
		
		// get content asynchronously
//console.log(this.getCfg(id, 'asynch'));
		if (this.getCfg(id, 'asynch'))
		{
			this.loadAsynch(id, this.getCfg(id, 'asynch_url'));
		}
		
		// handle event
		YAHOO.util.Event.preventDefault(e);
		YAHOO.util.Event.stopPropagation(e);		
	},
	
	fixPosition: function(id)
	{
		var el = document.getElementById(id);
		var el_reg = YAHOO.util.Region.getRegion(el);
		var cl_reg = YAHOO.util.Dom.getClientRegion();
		
		el.style.overflow = '';
		
		// make it smaller, if window height is not sufficient
		if (cl_reg.height < el_reg.height + 20)
		{
			var newHeight = cl_reg.height - 20;
			if (newHeight < 150)
			{
				newHeight = 150;
			}
			el.style.height = newHeight + "px";
			if (!this.widthFixed[id])
			{
				el.style.width = el_reg.width + 20 + "px";
				this.widthFixed[id] = true;
			}
			el_reg = YAHOO.util.Region.getRegion(el);
		}
		
		// to low -> show it higher
		if (cl_reg.bottom < el_reg.bottom)
		{
			YAHOO.util.Dom.setY(el, el_reg.y - (el_reg.bottom - cl_reg.bottom));
			el_reg = YAHOO.util.Region.getRegion(el);
		}
		
		// to far to the right -> show it more to the left
		if (cl_reg.right < el_reg.right)
		{
			YAHOO.util.Dom.setX(el, el_reg.x - (el_reg.right - cl_reg.right));
		}
		
		el.style.overflow = 'auto';
	},
	
	// hide all overlays
	hideAllOverlays: function (e) {
		for (var k in ilOverlayFunc.prototype.overlays)
		{
			var el = document.getElementById(k);
			var el_reg = YAHOO.util.Region.getRegion(el);
			if (!el_reg.contains(new YAHOO.util.Point(e.pageX , e.pageY)))
			{
				ilOverlayFunc.prototype.hide(null, k);
			}
		}
	},
	
	mouseOver: function (e, id)
	{
		this.closeCnt[id] = -1;
		//console.log("mouseOver");
	},

	mouseOut: function (e, id)
	{
		this.closeCnt[id] = this.waitMouseOut;
		this.closeProcess(id);
	},
	
	closeProcess: function (id)
	{
//console.log(this.closeCnt);
		if (this.closeCnt[id] > -1) 
		{
			this.closeCnt[id]--;
			if (this.closeCnt[id] == 0)
			{
				this.hide(null, id);
			}
		}
		if (this.closeCnt[id] > -1)
		{
			setTimeout("ilOverlay.closeProcess('" + id + "')", 200);
		}
	},

	loadAsynch: function (id, sUrl)
	{
		var cb =
		{
			success: this.asynchSuccess,
			failure: this.asynchFailure,
			argument: { id: id}
		};
	
		var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, cb);
		
		return false;
	},
	
	// handle asynchronous request (success)
	asynchSuccess: function(o)
	{
		// parse headers function
		function parseHeaders()
		{
			var allHeaders = headerStr.split("\n");
			var headers;
			for(var i=0; i < headers.length; i++)
			{
				var delimitPos = header[i].indexOf(':');
				if(delimitPos != -1)
				{
					headers[i] = "<p>" +
					headers[i].substring(0,delimitPos) + ":"+
					headers[i].substring(delimitPos+1) + "</p>";
				}
			return headers;
			}
		}
	
		// perform modification
		if(typeof o.responseText != "undefined")
		{
			// this a little bit complex procedure fixes innerHTML with forms in IE
			var newdiv = document.createElement("div");
			newdiv.innerHTML = o.responseText;
			var el = document.getElementById(o.argument.id);
			if (!el)
			{
				return;
			}
			el.innerHTML = '';
			el.appendChild(newdiv);
			
			// for safari: eval all javascript nodes
			if (YAHOO.env.ua.webkit != "0" && YAHOO.env.ua.webkit != "1")
			{
				//alert("webkit!");
				var els = YAHOO.util.Dom.getElementsBy(function(l){return true;}, "script", newdiv);
				for(var i= 0; i<=els.length; i++)
				{
					eval(els[i].innerHTML);
				}
			}
			ilOverlay.fixPosition(o.argument.id);
		}
	},
	
	// Success Handler
	asynchFailure: function(o)
	{
		//alert('FailureHandler');
	}

};
var ilOverlay = new ilOverlayFunc();
YAHOO.util.Event.addListener(document, "click",
	function(e) {ilOverlay.hideAllOverlays(e)});

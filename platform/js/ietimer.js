
/*
 * WARNNG: The function that is called will be
 *         in the 'global' scope.
 */


if(typeof(SERIA)=="undefined") alert("common.js not included");

SERIA.Timer = {
	_timerObject : new Array(),
	_timerVector : 0,

	_timerHandler : function(id)
	{
		var timerObj = SERIA.Timer._timerObject[id];
		var func = timerObj.func;
		var farg = timerObj.arg;
	
		if (timerObj.reuseable)
			alert('BUG: reuse was flagged: '+id)
		SERIA.Timer._timerObject[id].running = true;
		SERIA.Timer._timerObject[id].func = false;
		SERIA.Timer._timerObject[id].arg = false;
		var stop = SERIA.Timer._timerObject[id].stop;
		SERIA.Timer._timerObject[id].reuseable = true;
		if (!stop)
			func(farg);
	},
	_TimerControl : function(timerObj, timerID)
	{
		this.obj = timerObj;
		this.id = timerID;
		this.stop = function () {
			if (this.obj.uniq_id == this.id) {
				if (this.obj.running)
					return false;
				this.obj.stop = true;
				return true;
			} else
				return false;
		}
	},

	setTimeout : function(timeoutFunc, mstime, farg)
	{
		if (typeof(timeoutFunc) != 'function') {
			alert('SERIA.Timer.setTimeout: Expected function as first arg');
			throw new Exception('Expected function as first arg');
		}
		var i = 0;
		while (i < SERIA.Timer._timerVector) {
			if (SERIA.Timer._timerObject[i].reuseable)
				break;
			i++;
		}
		if (i >= SERIA.Timer._timerVector) {
			timerObj = new Object();
			timerObj.id = SERIA.Timer._timerVector++;
		} else
			timerObj = SERIA.Timer._timerObject[i];
		timerObj.uniq_id = Math.random() + timerObj.id;
		timerObj.func = timeoutFunc;
		timerObj.arg = farg;
		timerObj.running = false;
		timerObj.stop = false;
		timerObj.reuseable = false;
		SERIA.Timer._timerObject[timerObj.id] = timerObj;
		setTimeout('SERIA.Timer._timerHandler('+timerObj.id+');', mstime);
		return new this._TimerControl(timerObj, timerObj.uniq_id);
	}
}

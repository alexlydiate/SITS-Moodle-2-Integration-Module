/* Copyright Alex Lydiate 2011 Onwards */
var sits_block = {};

sits_block.SUCCESS = '0';
sits_block.ERROR_COHORT_FAILED_SITS_VALIDATION = '1';
sits_block.ERROR_FAILED_TO_CREATE_MAPPING = '2';
sits_block.ERROR_MAPPING_ALREADY_EXISTS = '3';
sits_block.ERROR_FAILED_TO_DELETE_MAPPING = '4';
sits_block.ERROR_FAILED_TO_RETRIEVE_MAPPING = '5';
sits_block.ERROR_FAILED_TO_INSTANTIATE_COHORT = '6';
sits_block.FAILED_TO_CREATE_GROUP = '7';
sits_block.FAILED_TO_ADD_TO_GROUP = ' 8';
sits_block.changes = [];
sits_block.appendCount = [];
sits_block.totalMaps = [];
sits_block.mapValueHasChanged = [];
sits_block.totalPeriods = 0;
sits_block.periodAppendCount = 0;
sits_block.newPeriodCount = 0;
sits_block.periodValueHasChanged = [];
sits_block.incompleteForm = [];
sits_block.cidForLastOperation;
sits_block.sUrl = location.protocol + '//' + location.hostname + '/blocks/sits/gui/client_async_request.php';

sits_block.isEven = function (int) {
	if (int % 2 === 0) {
		return true;
	} else {
		return false;
	}
};

sits_block.validate_bucs_id = function (bucs_id) {
	var patt = /^([a-z0-9])/i;
	if (bucs_id.match(patt) === null) {
		return false;
	} else {
		return true;
	}
};

sits_block.getTextContent = function (element) {
	if (window.DOMParser) {
		return element.textContent;
	} else {
		return element.innerText;
	}
};

sits_block.setTextContent = function (element, content) {
	if (window.DOMParser) {
		element.textContent = content;
	} else {
		element.innerText = content;
	}
};

sits_block.loadXMLString = function (txt) {
	var xmlDoc, parser;
	if (window.DOMParser) {
		parser = new DOMParser();
		xmlDoc = parser.parseFromString(txt, "text/xml");
	} else { // Internet Explorer
		xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async = "false";
		xmlDoc.loadXML(txt);
	}
	return xmlDoc;
};

sits_block.loadDate = function (dateString) {
	var dateArray = dateString.split('-');
	return new Date(dateArray[0], dateArray[1] - 1,dateArray[2]);	
};

sits_block.remove_new_map_form = function (courseid){
	sits_block.remove_element("id_" + courseid + "_new_map");
	sits_block.incompleteForm[courseid] = false;
};

sits_block.remove_element = function (id) {
	var element = YAHOO.util.Dom.get(id);
	element.parentNode.removeChild(element);
};

sits_block.blurScreen = function (txt) {
	var blurDiv = document.createElement("div"), 
	layerDiv = document.createElement("div"), 
	waitDivContainer = document.createElement("div"),
	waitDiv = document.createElement("div"),
	loaderDiv = document.createElement("div"),
	waitMessageDiv = document.createElement("div"),
	bodyElement = YAHOO.util.Dom.get('container');	
	blurDiv.id = "blurDiv";
	blurDiv.style.cssText = "position:absolute; top:0; right:0; width:" + screen.width + "px; height:auto; background-color: #ffffff; opacity:0.5; filter:alpha(opacity=50)";
	layerDiv.id = "layerDiv";
	layerDiv.style.cssText = "position:absolute; top:0; right:0; width:100%; height:auto";
	waitDivContainer.id = "waitDivContainer";
	waitDiv.id = "waitDiv";
	loaderDiv.id = "loaderDiv";
	waitMessageDiv.id = "waitMessageDiv";	
	waitMessageDiv.appendChild(document.createTextNode(txt));	
	waitDiv.appendChild(loaderDiv);
	waitDiv.appendChild(waitMessageDiv);
	waitDivContainer.appendChild(waitDiv);
	layerDiv.appendChild(waitDivContainer);		
	bodyElement.appendChild(blurDiv);
	bodyElement.appendChild(layerDiv);
};

sits_block.killBlur = function () {
	var layerDiv = YAHOO.util.Dom.get("layerDiv"),
	blurDiv = YAHOO.util.Dom.get("blurDiv");
	layerDiv.parentNode.removeChild(layerDiv);
	blurDiv.parentNode.removeChild(blurDiv);
};

sits_block.loaderToLoad = function (courseid) {
	//Switch loader to loading gif
	var plusButtonElement, plusButtonImage;
	plusButtonElement = YAHOO.util.Dom.get('id_' + courseid + '_plus');
	plusButtonElement.removeAttribute('href');
	plusButtonImage = YAHOO.util.Dom.get('id_' + courseid + '_plus_img');
	YAHOO.util.Dom.setAttribute(plusButtonImage, 'src', './images/liloader.gif');
};

sits_block.loaderToClose = function (courseid) {
	//Switch loader to close button
	var plusButtonImage, plusButtonElement;
	plusButtonImage = YAHOO.util.Dom.get('id_' + courseid + '_plus_img');
	YAHOO.util.Dom.setAttribute(plusButtonImage, 'src', './images/switch_minus.gif');
	plusButtonElement = YAHOO.util.Dom.get('id_' + courseid + '_plus');
	YAHOO.util.Dom.setAttribute(plusButtonElement, 'onclick', 'sits_block.hideMappingsForCourse(' + courseid + ')');
};

sits_block.loaderToOpen = function (courseid) {
	//Switch loader to open button
	var minusButtonElement, minusButtonImage;
	minusButtonElement = YAHOO.util.Dom.get('id_' + courseid + '_plus');
	YAHOO.util.Dom.setAttribute(minusButtonElement, 'id', 'id_' + courseid + '_plus');
	YAHOO.util.Dom.setAttribute(minusButtonElement, 'onclick', 'sits_block.loadMappingsForCourse(' + courseid + ')');
	minusButtonImage = YAHOO.util.Dom.get('id_' + courseid + '_plus_img');
	YAHOO.util.Dom.setAttribute(minusButtonImage, 'src', './images/switch_plus.gif');
};

sits_block.showControls = function (courseid) {
	var controlsElement = YAHOO.util.Dom.get('id_' + courseid + '_controls');
	YAHOO.util.Dom.setAttribute(controlsElement, 'style', 'display:block;');
};

sits_block.hideControls = function (courseid) {
	var controlsElement = YAHOO.util.Dom.get('id_' + courseid + '_controls');
	YAHOO.util.Dom.setAttribute(controlsElement, 'style', 'display:none;');
};

sits_block.showLoading = function (courseid, txt) {
	var loadMessageElement, loadingElement;
	sits_block.loaderToLoad(courseid);
	loadMessageElement = YAHOO.util.Dom.get('id_' + courseid + '_load_message');
	loadingElement = YAHOO.util.Dom.get('id_' + courseid + '_loading');
	sits_block.setTextContent(loadMessageElement, txt);
	YAHOO.util.Dom.setAttribute(loadingElement, 'style', 'display:block;');
};

sits_block.hideLoading = function (courseid, loaderClose) {
	var loadMessageElement, loadingElement;
	
	if (loaderClose) {
		sits_block.loaderToClose(courseid);
	} else {
		sits_block.loaderToOpen(courseid);
	}
	loadMessageElement = YAHOO.util.Dom.get('id_' + courseid + '_load_message');
	loadingElement = YAHOO.util.Dom.get('id_' + courseid + '_loading');
	sits_block.setTextContent(loadMessageElement, '');
	YAHOO.util.Dom.setAttribute(loadingElement, 'style', 'display:none');
};

sits_block.showGroupLoading = function (txt) {
	var loadingElement, textElement, controlsElement;
	
	controlsElement = YAHOO.util.Dom.get('group_controls');
	loadingElement = YAHOO.util.Dom.get('groups_load_message');
	textElement = YAHOO.util.Dom.get('groups_load_message_text');
	sits_block.setTextContent(textElement, txt);
	YAHOO.util.Dom.setAttribute(loadingElement, 'style', 'display:block;');
	YAHOO.util.Dom.setAttribute(controlsElement, 'style', 'display:none;');
};

sits_block.hideGroupLoading = function () {
	var loadingElement, controlsElement;
	
	controlsElement = YAHOO.util.Dom.get('group_controls');
	loadingElement = YAHOO.util.Dom.get('groups_load_message');
	YAHOO.util.Dom.setAttribute(loadingElement, 'style', 'display:none;');
	YAHOO.util.Dom.setAttribute(controlsElement, 'style', 'display:block;');
};

sits_block.showGroupNoMaps = function () {
	var mappingElement = YAHOO.util.Dom.get('select_mappings'),
	noMapElement = YAHOO.util.Dom.get('grp_no_maps'),
	groupSubmitElement = YAHOO.util.Dom.get('groupsubmit'),
	groupSelectElement = YAHOO.util.Dom.get('select_groups'),
	groupNameElement = YAHOO.util.Dom.get('groupname'),
	groupRadioExistElement = YAHOO.util.Dom.get('grp_radio_exist'),
	groupRadioCreateElement = YAHOO.util.Dom.get('grp_radio_create');
	
	groupRadioExistElement.disabled=true;
	groupRadioCreateElement.disabled=true;
	groupNameElement.disabled=true;
	groupSubmitElement.disabled=true;
	groupSelectElement.disabled=true;
	YAHOO.util.Dom.setAttribute(mappingElement,'style', 'display:none;');
	YAHOO.util.Dom.setAttribute(noMapElement,'style', 'display:block;');
};

sits_block.hideGroupNoMaps = function () {
	var mappingElement = YAHOO.util.Dom.get('select_mappings'),
	noMapElement = YAHOO.util.Dom.get('grp_no_maps'),
	groupSubmitElement = YAHOO.util.Dom.get('groupsubmit'),
	groupSelectElement = YAHOO.util.Dom.get('select_groups'),
	groupNameElement = YAHOO.util.Dom.get('groupname'),
	groupRadioExistElement = YAHOO.util.Dom.get('grp_radio_exist'),
	groupRadioCreateElement = YAHOO.util.Dom.get('grp_radio_create');
	
	groupRadioExistElement.disabled=false;
	groupRadioCreateElement.disabled=false;	
	groupNameElement.disabled=false;
	groupSubmitElement.disabled=false;
	groupSelectElement.disabled=false;
	YAHOO.util.Dom.setAttribute(mappingElement,'style', 'display:block;');
	YAHOO.util.Dom.setAttribute(noMapElement,'style', 'display:none;');
};

sits_block.swapControlsForLoadMessage = function (courseid, txt) {
	sits_block.hideControls(courseid);
	sits_block.showLoading(courseid, txt);
};

sits_block.swapLoadMessageForControls = function (courseid, closeLoader) {
	if (closeLoader) {
		sits_block.hideLoading(courseid, true);
	} else {
		sits_block.hideLoading(courseid, false);
	}
	sits_block.showControls(courseid);
};

sits_block.periodSwapControlsForLoad = function () {
	var controlsElement, loadingElement;
	
	loadingElement = YAHOO.util.Dom.get('period_code_load');
	controlsElement = YAHOO.util.Dom.get('period_code_controls');
	YAHOO.util.Dom.setAttribute(loadingElement, 'style', 'display:none;');
	YAHOO.util.Dom.setAttribute(controlsElement, 'style', 'display:block;');
};

sits_block.periodSwapLoadForControls = function () {
	var controlsElement, loadingElement;
	
	loadingElement = YAHOO.util.Dom.get('period_code_load');
	controlsElement = YAHOO.util.Dom.get('period_code_controls');
	YAHOO.util.Dom.setAttribute(loadingElement, 'style', 'display:block;');
	YAHOO.util.Dom.setAttribute(controlsElement, 'style', 'display:none;');
};

sits_block.showAddingCohortLoader = function () {
	var element;
	
	element = YAHOO.util.Dom.get('add_cohort_title');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:none');
	element = YAHOO.util.Dom.get('add_cohort_loader');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:block');
	element = YAHOO.util.Dom.get('id_add_cohort_buttons');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:none');
};

sits_block.showAddingCohortTitle = function () {	
	var element;
	
	element = YAHOO.util.Dom.get('add_cohort_title');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:block');
	element = YAHOO.util.Dom.get('add_cohort_loader');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:none');
	element = YAHOO.util.Dom.get('id_add_cohort_buttons');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:block');
};

sits_block.showNoCurrentGroups = function () {
	var element;
	
	element = YAHOO.util.Dom.get('add_to_existing');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:none');
	element = YAHOO.util.Dom.get('no_existing_groups');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:block');
};

sits_block.showAddToExistingGroups = function () {
	var element;
	
	element = YAHOO.util.Dom.get('add_to_existing');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:block');
	element = YAHOO.util.Dom.get('no_existing_groups');
	YAHOO.util.Dom.setAttribute(element, 'style', 'display:none');
};

sits_block.setDatesDisabledAttribute = function (mapId, disabled) {
	var element;
	
	element = YAHOO.util.Dom.get('id_' + mapId + '_map_day');
	element.disabled = disabled;
	element = YAHOO.util.Dom.get('id_' + mapId + '_map_month');
	element.disabled = disabled;
	element = YAHOO.util.Dom.get('id_' + mapId + '_map_year');
	element.disabled = disabled;
};

sits_block.createMappingXML = function (course_id, sits_code, type, academic_year, period_code, year_group,
		unenrol, date) {	
	var XML;
	
	XML = "<?xml version='1.0' standalone='yes'?>";
	XML += '<map>';
	XML += '<course_id>' + course_id + '</course_id>';
	XML += '<unenrol>' + unenrol + '</unenrol>';
	XML += '<default>' + 'false' + '</default>';
	XML += '<start_date></start_date>';
	XML += '<end_date>' + date + '</end_date>';	
	if (type === 'module') {
		XML += '<cohort>';
		XML += '<type>module</type>';
		XML += '<sits_code>' + sits_code + '</sits_code>';
		XML += '<acyear>' + academic_year + '</acyear>';
		XML += '<period_code>' + period_code + '</period_code>';
		XML += '</cohort>';		
	}	
	if (type === 'programme') {
		XML += '<cohort>';
		XML += '<type>program</type>';
		XML += '<sits_code>' + sits_code + '</sits_code>';
		XML += '<acyear>' + academic_year + '</acyear>';
		XML += '<year_group>' + year_group + '</year_group>';
		XML += '</cohort>';
	}	
	XML += '</map>';	
	return XML;	
};

sits_block.toggle_dates = function (mapId)
{
	var element = YAHOO.util.Dom.get('id_' + mapId + '_map_unenrol_type'), disabled;
	
	if (1 === element.selectedIndex) {
		disabled = false;
	} else {
		disabled = true;
	}
	
	sits_block.setDatesDisabledAttribute(mapId, disabled);
};

sits_block.get_years = function (date)
{
    var start_year = 2000, 
    count,
    end_year = 2020,
    years_html = '',
    year = date.getFullYear();
    
    for(count = start_year; count <= end_year; count++) {
        if (count===year)
        {
            years_html += '<option value="' + count.toString() + '" selected="selected">' + count.toString() + '</option>';
        }
        else
        {
            years_html += '<option value="' + count.toString() + '">' + count.toString() + '</option>';
        }
    }
    return years_html;
};

sits_block.get_months = function (date)
{
    var months_html = '',
    current_month, corrected_month,
    month = date.getMonth(),
    months = ['JAN','FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    
    for(current_month = 0; current_month < 12; current_month++)
    {
    	corrected_month = current_month;
    	corrected_month++;
        if (current_month===month)
        {
            months_html += '<option value="' + corrected_month.toString() + '" selected="selected">' + months[current_month] + '</option>';
        }
        else
        {
            months_html += '<option value="' + corrected_month.toString() + '">' + months[current_month] + '</option>';
        }
    }
    return months_html;
};

sits_block.get_days = function (date)
{
    var days_html = '',
    count,
    day = date.getDate();
    
    for(count = 1; count <= 31; count++)
    {
        if (count===day)
        {
            days_html += '<option value="' + count.toString() + '" selected="selected">' + count.toString() + '</option>';
        }
        else
        {
            days_html += '<option value="' + count.toString() + '">' + count.toString() + '</option>';
        }
    }
    return days_html;
};

sits_block.get_period_options = function () {
	var html = '<option value="S1" selected="selected">S1</option>';
	html += '<option value="S2">S2</option>';
	html += '<option value="S3">S3</option>';
	html += '<option value="T1">T1</option>';
	html += '<option value="T2">T2</option>';
	html += '<option value="T3">T3</option>';
	html += '<option value="AY">AY</option>';
	html += '<option value="DIS">DIS</option>';
	html += '<option value="MOD">MOD</option>';
	html += '<option value="M01">M01</option>';
	html += '<option value="M02">M02</option>';
	html += '<option value="M03">M03</option>';
	html += '<option value="M04">M04</option>';
	html += '<option value="M05">M05</option>';
	html += '<option value="M06">M06</option>';
	html += '<option value="M07">M07</option>';
	html += '<option value="M08">M08</option>';
	html += '<option value="M09">M09</option>';
	html += '<option value="M10">M10</option>';
	html += '<option value="M11">M11</option>';
	html += '<option value="M12">M12</option>';
	
	return html;
};

sits_block.get_acyear_options = function () {
	var html = '<option value="2008/9">2008/9</option>';
	
	html += '<option value="2009/0">2009/0</option>';
	html += '<option value="2010/1">2010/1</option>';
	html += '<option value="2011/2" selected="true">2011/2</option>';
	html += '<option value="2012/3">2012/3</option>';
	html += '<option value="2013/4">2013/4</option>';
	return html;
};

sits_block.get_yos_options = function () {
	var html = '<option value="0" selected="selected">All</option>';
	html += '<option value="1">1</option>';
	html += '<option value="2">2</option>';
	html += '<option value="3">3</option>';
	html += '<option value="4">4</option>';
	html += '<option value="5">5</option>';
	html += '<option value="6">6</option>';
	html += '<option value="7">7</option>';
	html += '<option value="8">8</option>';
	html += '<option value="9">9</option>';
	html += '<option value="10">10</option>';
	html += '<option value="11">11</option>';
	html += '<option value="12">12</option>';
	html += '<option value="13">13</option>';
	html += '<option value="14">14</option>';
	html += '<option value="15">15</option>';
	html += '<option value="16">16</option>';
	html += '<option value="17">17</option>';
	return html;
};

sits_block.get_unenrolment_types = function (mapping)
{
	var specified_value = mapping.getElementsByTagName("specified")[0].firstChild.data,
	manual_value = mapping.getElementsByTagName("manual")[0].firstChild.data,
	default_map = mapping.getElementsByTagName("default")[0].firstChild.data,
	ui_map_type,
	types_html;	
	if (specified_value === '0' && manual_value === '0') {
		ui_map_type = 'automatic';
	}
	if (specified_value === '1' && manual_value === '0') {
		ui_map_type = 'specified';
	}
	if (specified_value === '0' && manual_value === '1' && default_map === '0') {
		ui_map_type = 'manual'; 
	}	
	if (default_map === '1') {
	switch(ui_map_type) {
		case 'specified':
			types_html = '<option value="automatic">Sync</option>' +
			'<option value="specified" selected="selected">Specified</option>';
		break;
		//case 'automatic':
		default:
			types_html = '<option value="automatic" selected="selected">Sync</option>' +
			'<option value="specified">Specified</option>';
		break;
		}
	} else {
	    switch(ui_map_type) {
		case 'manual':
			types_html = '<option value="automatic">Sync</option>' +
			'<option value="specified">Specified</option>' +  
			'<option value="manual" selected="selected">Manual</option>';
		break;
		case 'specified':
			types_html = '<option value="automatic">Sync</option>' +
			'<option value="specified" selected="selected">Specified</option>' +  
			'<option value="manual">Manual</option>';
		break;
		//case 'automatic':
		default:
			types_html = '<option value="automatic" selected="selected">Sync</option>' +
			'<option value="specified">Specified</option>' +  
			'<option value="manual">Manual</option>';
		break;
	    }
	}	
    return types_html;
};

sits_block.appendMappingToPage = function (mapping, newMap) {
	
	var html, yeargroup, periodcode, saveElement,
	sitscode = mapping.getElementsByTagName("sits_code")[0].firstChild.data,
	acyear = mapping.getElementsByTagName("acyear")[0].firstChild.data,
	course_id = mapping.getElementsByTagName("course_id")[0].firstChild.data,
	default_map = mapping.getElementsByTagName("default")[0].firstChild.data,
	id = mapping.getElementsByTagName("id")[0].firstChild.data,
	date = sits_block.loadDate(mapping.getElementsByTagName("end_date")[0].firstChild.data),
	year_options = sits_block.get_years(date),
	month_options = sits_block.get_months(date),
	day_options = sits_block.get_days(date),
	unenrol_type = sits_block.get_unenrolment_types(mapping),
	mapDiv = document.createElement('div');
	
	if (mapping.getElementsByTagName("type")[0].firstChild.data === 'module') {
		periodcode = mapping.getElementsByTagName("period_code")[0].firstChild.data;
		yeargroup = 'N/A';
	} else {	
		yeargroup = mapping.getElementsByTagName("year_group")[0].firstChild.data;
		if (yeargroup === '0') {
			yeargroup = 'All';
		}
		periodcode = 'N/A';
	}
	html = '<p class="course_detail" id="id_' + id + '">';
	if (newMap === true) {
		html += '<strong class="mpg_category">COHORT SAVED: </strong>';
	}
	html += '<span class="mpg_category">SAMIS Code: </span>';
	html += '<strong class="mpg_category">' + sitscode + '</strong>';
	html += '<span class="mpg_category"> Academic Year: </span><strong class="mpg_category">' + acyear + '</strong>';
	html += '<span class="mpg_category"> Period: </span><strong class="mpg_category">' + periodcode + '</strong>';
	html += '<span class="mpg_category"> Year of Study: </span><strong class="mpg_category">' + yeargroup + '</strong>';
	html += '<strong class="hiddenfield">' + id + '</strong>';
	html += '<strong class="hiddenfield">' + id + '</strong>';
	html += '<br/>';
	html += '<span class="unenrol-selects"><label for="id_' + id + '"><span class="mpg_category">Unenrol Method: </span></label>';
	html += '<select id="id_' + id + '_map_unenrol_type" class="map_select" onchange="sits_block.toggle_dates(' + id + ');sits_block.enable_save(' + course_id + '); sits_block.mapValueChange(' + id + ')">';
	html += unenrol_type;
	html += '</select>';
	html += '</span>';
	html += '<span class="date-selects"><span class="mpg_category"> Date: </span>';
	html += '<select class="day" id="id_' + id + '_map_day" class="map_select" onchange="sits_block.enable_save(' + course_id + '); sits_block.mapValueChange(' + id + ')">';
	html += day_options;
	html += '</select>';
	html += '<select class="month" id="id_' + id + '_map_month"  class="map_select" onchange="sits_block.enable_save(' + course_id + '); sits_block.mapValueChange(' + id + ')">';
	html += month_options;
	html += '</select>';
	html += '<select class="year" id="id_' + id + '_map_year" class="map_select" onchange="sits_block.enable_save(' + course_id + '); sits_block.mapValueChange(' + id + ')">';
	html += year_options;
	html += '</select>';
	if (default_map === '0') {
		html += '&nbsp&nbsp<input type="submit" class="map_submit" id="id_' + id + '_map_remove"  value ="Remove Cohort" onclick="sits_block.toggle_delete(this,' + id + '); sits_block.enable_save(' + course_id + ')"/>';
	} else {
		html += '&nbsp&nbsp<input type="submit" style="display:none" class="map_submit" id="id_' + id + '_map_remove"  value ="Remove Cohort" onclick="sits_block.toggle_delete(this,' + id + '); sits_block.enable_save(' + course_id + ')"/>';
		html += '<b>&nbsp&nbsp Default</b>';
	}
	html += '</span>';
	html += '</p>';
	if (newMap === true) {
		mapDiv.style.cssText = 'background-color: #9AB27C;';
	} else {
		if (sits_block.isEven(sits_block.appendCount[course_id])) {
			mapDiv.style.cssText = 'background-color: #DFDFDF;';
		} else {
			mapDiv.style.cssText = 'background-color: #EFEFEF;';
		}
	}
	mapDiv.innerHTML = html;
	mapDiv.id = 'id_' + id + '_map';
	mapDiv.className = id;
	YAHOO.util.Dom.get('id_' + course_id + '_mappings').appendChild(mapDiv);
	sits_block.toggle_dates(id);
	sits_block.appendCount[course_id]++;
	if (sits_block.appendCount[course_id] === sits_block.totalMaps[course_id]) {
		sits_block.showControls(course_id);
		sits_block.loaderToClose(course_id);
		if (sits_block.changes[course_id] !== true) {
			saveElement = YAHOO.util.Dom.get('id_' + course_id + '_save');
			YAHOO.util.Dom.setAttribute(saveElement, 'disabled', 'disabled');
		}
	}
};

sits_block.appendNoMapsToCourseDiv = function (courseid) {
	if(YAHOO.util.Dom.get('id_' + courseid + '_no_maps')){
	}else{
		var noMapDiv = document.createElement("div"), html,
		element = YAHOO.util.Dom.get('id_' + courseid + '_mappings'),
		saveElement = YAHOO.util.Dom.get('id_' + courseid + '_save');		
		html = '<p class="course_detail" id="id_' + courseid + '">';
		html += 'No current mappings exist for this course.';
		html +='</p>';
		noMapDiv.innerHTML = html;
		noMapDiv.id = 'id_' + courseid + '_no_maps';
		element.appendChild(noMapDiv);
	}
	sits_block.showControls(courseid);
	sits_block.loaderToClose(courseid);

	YAHOO.util.Dom.setAttribute(saveElement, 'disabled', 'disabled');	
};

sits_block.removeNoMapsToCourseDiv = function (courseid) {
	var noMapElement = YAHOO.util.Dom.get('id_' + courseid + '_no_maps');
	
	if (noMapElement !== null) {
		noMapElement.parentNode.removeChild(noMapElement);
	}
};

sits_block.appendMappingToGroupsPage = function (mapping) {
	var sitscode = mapping.getElementsByTagName("sits_code")[0].firstChild.data,
	acyear = mapping.getElementsByTagName("acyear")[0].firstChild.data,
	course_id = mapping.getElementsByTagName("course_id")[0].firstChild.data,
	periodcode, yeargroup, id, option, element;
	
	if (mapping.getElementsByTagName("type")[0].firstChild.data === 'module') {
		periodcode = mapping.getElementsByTagName("period_code")[0].firstChild.data;
		yeargroup = 'N/A';
	} else {	
		yeargroup = mapping.getElementsByTagName("year_group")[0].firstChild.data;
		periodcode = 'N/A';
	}
	
	id = mapping.getElementsByTagName("id")[0].firstChild.data;	
	option = document.createElement('option');
	sits_block.setTextContent(option, 'SAMIS code:' + sitscode + ', Academic Year: ' + acyear + ' Period: ' + periodcode + ', Year of Study: ' + yeargroup);
	if (sits_block.isEven(sits_block.appendCount[course_id])) {
		option.style.cssText = 'background-color: #DFDFDF;';
	} else {
		option.style.cssText = 'background-color: #EFEFEF;';
	}
	option.id = id;
	element = YAHOO.util.Dom.get('select_mappings');
	element.appendChild(option);
	sits_block.appendCount[course_id]++;
	if (sits_block.appendCount[course_id] === sits_block.totalMaps[course_id]) {
		sits_block.hideGroupLoading();
	}
};

sits_block.appendNoMapsToGroupsPage = function (courseid) {
	var html,
	noMapDiv = document.createElement("div"),
	mappingElement = YAHOO.util.Dom.get('steps_two_and_three'),
	noMapElement = YAHOO.util.Dom.get('grp_no_maps');
	
	noMapDiv.id = 'no_map_div';
	html = '<p class="course_detail" id="' + courseid + '">';
	html += 'No current mappings exist for this course.';
	html +='</p>';
	sits_block.setTextContent(noMapDiv, html);	
	YAHOO.util.Dom.setAttribute(mappingElement,'style', 'display:none;');
	YAHOO.util.Dom.setAttribute(noMapElement,'style', 'display:block;');
};

sits_block.appendGroupToSelect = function (groupXml) {
	var select = YAHOO.util.Dom.get('select_groups'),
	option = document.createElement("option");
	
	sits_block.setTextContent(option, groupXml.getElementsByTagName("name")[0].firstChild.data);
	option.value = groupXml.getElementsByTagName("id")[0].firstChild.data;
	select.appendChild(option);
};

sits_block.asyncRequest = function (op, xml) {	

	var responseSuccess, responseFailure, callback, i, n, errors, cid, xmlElements, xmlDoc, deleted, updated, 
	message, getMapXML, srcXmlDoc, courses, delElement;
	
	switch(op) {
		case 'create_map':
			    responseSuccess = function (o) {
				if (o.responseText.match(/xml version/)) {
					//Got an XML doc returned, must be a success				
					xmlDoc = sits_block.loadXMLString(o.responseText);
					cid = xmlDoc.getElementsByTagName("course_id")[0].firstChild.data;
					xmlElements = xmlDoc.getElementsByTagName("map");
					sits_block.totalMaps[cid] = xmlElements.length;
					sits_block.appendCount[cid] = 0;
					if (xmlElements.length > 0) {
						sits_block.removeNoMapsToCourseDiv(cid);
						sits_block.appendMappingToPage(xmlElements[0], true);						
					}
					sits_block.swapLoadMessageForControls(cid, true);
					sits_block.remove_new_map_form(cid);
				} else {
					switch(o.responseText) {
						case sits_block.ERROR_COHORT_FAILED_SITS_VALIDATION:
							//sits_block.showAddingCohortTitle();
							alert('The cohort you have defined does not exist.  Please review and try again.');
							//YAHOO.util.Dom.get('samis-module').activate();
							sits_block.swapLoadMessageForControls(sits_block.cidForLastOperation, true);
						break;
						case sits_block.ERROR_FAILED_TO_INSTANTIATE_COHORT:
							//sits_block.showAddingCohortTitle();
							alert('An error has meant the cohort you have defined has not been recognised.  Please contact an administrator.');
							//YAHOO.util.Dom.get('samis-module').activate();
							sits_block.swapLoadMessageForControls(sits_block.cidForLastOperation, true);
						break;
						case sits_block.ERROR_FAILED_TO_CREATE_MAPPING:
							//sits_block.showAddingCohortTitle();
							alert('The application failed to create the mapping.  Please contact an administrator');
							//YAHOO.util.Dom.get('samis-module').activate();
							sits_block.swapLoadMessageForControls(sits_block.cidForLastOperation, true);
						break;
						case sits_block.ERROR_MAPPING_ALREADY_EXISTS:
							//sits_block.showAddingCohortTitle();
							alert('This mapping already exists.');
							//YAHOO.util.Dom.get('samis-module').activate();
							sits_block.swapLoadMessageForControls(sits_block.cidForLastOperation, true);
						break;
						default:
							//sits_block.showAddingCohortTitle();
							alert('An unidentified error has occured.  Please contact an administrator');
							sits_block.swapLoadMessageForControls(sits_block.cidForLastOperation, true);
						break;
					}
				}
			};
		break;
		case 'batch':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);				
				errors = xmlDoc.getElementsByTagName("error");
				courses = xmlDoc.getElementsByTagName("course_id");
				deleted = xmlDoc.getElementsByTagName("deleted");
				updated = xmlDoc.getElementsByTagName("updated");
				if (errors.length > 0) {
					for (i=0;i<errors.length;i++) { 
						alert(errors[i].firstChild.data);
					}
				}
				//load all affected courses
				for(n=0; n<deleted.length; n++) {
				    delElement = YAHOO.util.Dom.get('id_' + deleted[n].firstChild.data + '_map');
				    delElement.parentNode.removeChild(delElement);
			    }
				for(n=0; n<updated.length; n++) {
					sits_block.mapValueHasChanged[updated[n].firstChild.data] = false;
		        }
				for (i=0;i<courses.length;i++) {
					//sits_block.hideMappingsForCourse(courses[i].firstChild.data);
					sits_block.disable_save(courses[i].firstChild.data);
					sits_block.swapLoadMessageForControls(courses[i].firstChild.data, true);
					sits_block.set_group_options();
				}	
			};
		break;
		case 'sync':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);				
				errors = xmlDoc.getElementsByTagName("error");
				courses = xmlDoc.getElementsByTagName("course_id");
				if (errors.length > 0) {
					for (i=0;i<errors.length;i++) { 
						alert(errors[i].firstChild.data);
					}			
				}
				for (i=0;i<courses.length;i++) {
					sits_block.swapLoadMessageForControls(courses[i].firstChild.data, true);
				}
				
			};
		break;	
		case 'add_user':
		case 'sync_all':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);
				message = xmlDoc.getElementsByTagName("message");
				sits_block.killBlur();
				alert(message[0].firstChild.data);
			};
		break;
		case 'get_map_ids':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);
				cid = xmlDoc.getElementsByTagName("cid")[0].firstChild.data;
				xmlElements = xmlDoc.getElementsByTagName("id");
				sits_block.totalMaps[cid] = xmlElements.length;
				sits_block.appendCount[cid] = 0;
				if (xmlElements.length > 0) {
					for (i=0;i<xmlElements.length;i++) {
						getMapXML = "<?xml version='1.0' standalone='yes'?>";
						getMapXML += "<map><id>" + xmlElements[i].firstChild.data + "</id></map>"; 
						sits_block.asyncRequest('get_map',getMapXML);
					}
				} else {
					srcXmlDoc = sits_block.loadXMLString(xml);					
					sits_block.appendNoMapsToCourseDiv(srcXmlDoc.getElementsByTagName("course_id")[0].firstChild.data);
				}
			};
		break;
		case 'get_map':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);
				xmlElements = xmlDoc.getElementsByTagName("map");
				if (xmlElements.length > 0) {
					sits_block.appendMappingToPage(xmlElements[0], false);						
				} else {
					srcXmlDoc = sits_block.loadXMLString(xml);					
					sits_block.appendNoMapsToCourseDiv(srcXmlDoc.getElementsByTagName("course_id")[0].firstChild.data);
				}
			};
		break;
		case 'get_map_ids_group':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);
				cid = xmlDoc.getElementsByTagName("cid")[0].firstChild.data;
				xmlElements = xmlDoc.getElementsByTagName("id");
				sits_block.totalMaps[cid] = xmlElements.length;
				sits_block.appendCount[cid] = 0;
				if (xmlElements.length > 0) {
					for (i=0;i<xmlElements.length;i++) {
						getMapXML = "<?xml version='1.0' standalone='yes'?>";
						getMapXML += "<map><id>" + xmlElements[i].firstChild.data + "</id></map>"; 
						sits_block.asyncRequest('get_map_group', getMapXML);
					}
					sits_block.hideGroupLoading();
				} else {
					sits_block.showGroupNoMaps();
					sits_block.hideGroupLoading();
				}
			};
		break;
		case 'get_map_group':
			    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);
				xmlElements = xmlDoc.getElementsByTagName("map");
				if (xmlElements.length > 0) {	
					sits_block.appendMappingToGroupsPage(xmlElements[0]);						
				} else {
					sits_block.showGroupNoMaps();
					sits_block.hideGroupLoading();
				}
			};
		break;
		case 'get_groups':
		    responseSuccess = function (o) {
				xmlDoc = sits_block.loadXMLString(o.responseText);
				cid = xmlDoc.getElementsByTagName("cid")[0].firstChild.data;
				xmlElements = xmlDoc.getElementsByTagName("group");
				if (xmlElements.length > 0) {
					for (i=0;i<xmlElements.length;i++) {
						sits_block.appendGroupToSelect(xmlElements[i]);
					}
					sits_block.showAddToExistingGroups();
				} else {
					sits_block.showNoCurrentGroups();
				}
			};
		break;
		case 'create_group':
		    responseSuccess = function (o) {
				switch(o.responseText) {
				case sits_block.SUCCESS:
					srcXmlDoc = sits_block.loadXMLString(xml);
					cid = srcXmlDoc.getElementsByTagName("course_id")[0].firstChild.data;
					sits_block.update_group_list(cid);
					sits_block.hideGroupLoading();					
				break;
				case sits_block.FAILED_TO_CREATE_GROUP:
					sits_block.hideGroupLoading();
					alert('An error has meant that the application failed to create the group');
				break;				
				case sits_block.FAILED_TO_ADD_TO_GROUP:
					sits_block.hideGroupLoading();
					alert('An error has meant that the application failed to add the chosen mapped cohorts to the group');
				break;
				default:
					sits_block.hideGroupLoading();
					alert('An unidentified error has occured.  Please contact an administrator');
				break;
				}
			};
		break;
		case 'add_to_group':
		    responseSuccess = function (o) {
				switch(o.responseText) {
				case sits_block.SUCCESS:
					sits_block.hideGroupLoading();
				break;
				case sits_block.FAILED_TO_ADD_TO_GROUP:
					sits_block.hideGroupLoading();
					alert('An error has meant that the application failed to add the chosen mapped cohorts to the group');			
					break;
				default:
					sits_block.hideGroupLoading();
					alert('An unidentified error has occured.  Please contact an administrator');
				break;
				}
			};
		break;
		case 'get_periods':
		    responseSuccess = function (o) {
		    	xmlDoc = sits_block.loadXMLString(o.responseText);
				xmlElements = xmlDoc.getElementsByTagName("period");
				sits_block.totalPeriods = xmlElements.length;
				sits_block.periodAppendCount = 0;
				if (xmlElements.length > 0) {
					for (i=0;i<xmlElements.length;i++) {
						sits_block.appendPeriod(xmlElements[i], false);
					}
				} else {			
					sits_block.appendNoAlteredPeriods();
				}
		    };	
		break;
		case 'save_periods':
		    responseSuccess = function (o) {
				switch(o.responseText) {
				case sits_block.SUCCESS:
					sits_block.populate_period_codes();
					sits_block.periodSwapControlsForLoad();
				break;
				case sits_block.FAILED_TO_SAVE_PERIODS:
					alert('An error has meant that the application failed to save the period alterations');
				break;				
			}
		};
		break;
		case 'reset_sync_flag':
		    responseSuccess = function (o) {
				switch(o.responseText) {
				case sits_block.SUCCESS:
					alert('The Full Sync flag has been reset');
				break;
				case sits_block.FAILED_TO_SAVE_PERIODS:
					alert('An error has meant that the application failed to reset the Full Sync flag');
				break;				
			}
		};
		break;
	}
	 
	responseFailure = function (o) {
		alert('The application has failed to communicate successfully with the server.');
	};
	 
	callback = {
	  success:responseSuccess,
	  failure:responseFailure
	};
	
	transaction = YAHOO.util.Connect.asyncRequest('POST', sits_block.sUrl, callback, 'op=' + op + '&xml=' + escape(xml));
};
	
sits_block.create_batch_xml = function (courseid)
{	
	var mapping_id, xml, i, removeButton, day, month, year, type,
	mappingsElement = YAHOO.util.Dom.get('id_' + courseid + '_mappings');
	
	xml = "<?xml version='1.0' standalone='yes'?>";
	xml += "<batch_actions>";
	xml += "<course id='" + courseid + "'>";
	for (i = 0; i < mappingsElement.childNodes.length; i++) {
		mapping_id = mappingsElement.childNodes[i].className;
		if (mapping_id !== null) {
			removeButton = YAHOO.util.Dom.get('id_' + mapping_id + '_map_remove');
			if (removeButton.value === 'Restore Cohort') {
				xml += "<map id='" + mapping_id + "'>";
				xml += '<delete>true</delete>';
				xml += '</map>';
			} else {
				if (sits_block.mapValueHasChanged[mapping_id]) {
					xml += "<map id='" + mapping_id + "'>";
					day = YAHOO.util.Dom.get('id_' + mapping_id + '_map_day');
					month = YAHOO.util.Dom.get('id_' + mapping_id + '_map_month');
					year = YAHOO.util.Dom.get('id_' + mapping_id + '_map_year');				
					type = YAHOO.util.Dom.get('id_' + mapping_id + '_map_unenrol_type');	
					xml += '<end_date>' + year.value + '-' + month.value + '-' + day.value + '</end_date>';
					xml += '<type>' + type.value + '</type>';
					xml += '</map>';
				}
			}
		}
	}
	
	//New Map handling
	xml += "</course>";
	xml += '</batch_actions>';
	return xml;
};

sits_block.create_batch_period_xml = function () {	
	var periodTable, periodTableRows, row, id, xml, i, code, acyear, start, end, startDay, 
	startMonth, startYear, endDay, endMonth, endYear, newAlter, revert;
	
	xml = "<?xml version='1.0' standalone='yes'?>";
	xml += "<periods_to_alter>";
	periodTable = YAHOO.util.Dom.get('period_code_table');
	periodTableRows = periodTable.getElementsByTagName("tr");
	for (i = 1; i<periodTableRows.length; i++) {
		row = periodTableRows[i];
		id = row.className;
		startYear = YAHOO.util.Dom.get('id_p_start_year_' + id).value;
		startMonth = YAHOO.util.Dom.get('id_p_start_month_' + id).value;
		startDay = YAHOO.util.Dom.get('id_p_start_day_' + id).value;
		start = startYear + '-' + startMonth + '-' + startDay;
		endYear = YAHOO.util.Dom.get('id_p_end_year_' + id).value;
		endMonth = YAHOO.util.Dom.get('id_p_end_month_' + id).value;
		endDay = YAHOO.util.Dom.get('id_p_end_day_' + id).value;
		end = endYear + '-' + endMonth + '-' + endDay;
		if (id.substring(0,3) !== 'new') {
			if (sits_block.periodValueHasChanged[id] === true) {
				newAlter = '0';
				code = YAHOO.util.Dom.get('id_p_code_' + id);
				acyear = YAHOO.util.Dom.get('id_p_acyear_' + id);
				revert = YAHOO.util.Dom.get('id_p_revert_' + id);
				if (revert.checked) {
					revert = '1';
				} else {
					revert = '0';
				}
				xml += sits_block.create_period_xml(sits_block.getTextContent(code), sits_block.getTextContent(acyear), start, end, revert, id, newAlter, false);
			}
		} else {
			newAlter = '1';
			code = YAHOO.util.Dom.get('period_code_select_' + id);
			acyear = YAHOO.util.Dom.get('period_acyear_select_' + id);
			xml += sits_block.create_period_xml(code.value, acyear.value, start, end, '0', id, newAlter, false);
		}
	}
	xml += "</periods_to_alter>";
	return xml;
};

sits_block.loadMappingsForCourse = function (courseid) {
	//Initialise new mapping count
	var xml, 
	currentDiv = YAHOO.util.Dom.get('id_' + courseid + '_mappings'),
	mapDiv = document.createElement("div");
	
	sits_block.changes[courseid] = false;
	//Check if mappings are loaded, if so remove in order to reload 
	if (currentDiv !== null) {
		currentDiv.parentNode.removeChild(currentDiv);
	}
	//Swap to loading gif
	sits_block.loaderToLoad(courseid);
	//Add mappings div	
	mapDiv.id = 'id_' + courseid + '_mappings';
	YAHOO.util.Dom.get('id_' + courseid + '_content').appendChild(mapDiv);	
	//build XML for request
	xml = "<?xml version='1.0' standalone='yes'?>";
	xml += '<get_maps><course_id>' + courseid + '</course_id></get_maps>';
    //Kick off request
	sits_block.asyncRequest('get_map_ids', xml);
};

sits_block.hideMappingsForCourse = function (courseid) {
	var saveConfirm = true, currentDiv, controlsElement;
	
	if (sits_block.changes[courseid] === true) {					
		saveConfirm = confirm('You have made unsaved changes.  Click OK to close the course mappings without saving, or click cancel and then Save Changes.');
	}
		if (saveConfirm) {
			currentDiv = YAHOO.util.Dom.get('id_' + courseid + '_mappings');
		//Check if mappings are loaded, if so remove in order to reload 
		if (currentDiv !== null) {
			currentDiv.parentNode.removeChild(currentDiv);
		}
		//Swap Plus/Minus buttons
		sits_block.loaderToOpen(courseid);
		//Hide Controls
		controlsElement = YAHOO.util.Dom.get('id_' + courseid + '_controls');
		YAHOO.util.Dom.setAttribute(controlsElement,'style', 'display:none;');
	}
};

sits_block.add_coursem = function (moodlecourse) {
	window.open("samis_user_interface_addm.php?moodlecourse=" + moodlecourse,"mockup_4m","height=500,width=600,status=no,toolbar=no,menubar=no,scrollbars=1,location=no");
};

sits_block.view_groups_page = function () {
	var xml, courseSelect = YAHOO.util.Dom.get('grp_course'),
	courseid = courseSelect.options[courseSelect.selectedIndex].value;
	
	window.open('/group/index.php?id=' + courseid, '', 'height = 600px, width = 800px, scrollbars=yes');
};

sits_block.save_course_changes = function (courseid) {
	var confirmed, xml;
	
	confirmed = confirm("Please click 'OK' to save your changes.\n\n(If you have removed a cohort mapping, the associated students will be removed at this point).");
	if (confirmed)
	{
		xml = sits_block.create_batch_xml(courseid);
		sits_block.swapControlsForLoadMessage(courseid, 'Saving Changes - please wait');
		sits_block.asyncRequest('batch',xml);
		sits_block.changes[courseid]=false;
	}
};

sits_block.sync_course = function (course_id) {
	var confirmed, saveConfirm, xml;
	
	confirmed = confirm('Please confirm you would like to sync this Moodle course with SAMIS');	
	if (confirmed) {
		saveConfirm = true;
		if (sits_block.changes[course_id] === true) {					
			saveConfirm = confirm('You have made unsaved changes.  Click OK to continue without saving, or click cancel and then Save Changes before syncing.');
		}
		if (saveConfirm) {
			xml = "<?xml version='1.0' standalone='yes'?><syncs><sync>" + course_id + "</sync></syncs>";
			sits_block.swapControlsForLoadMessage(course_id, 'Syncing Course - please wait');
			sits_block.asyncRequest('sync',xml);
		}
	}
};

sits_block.displayMapLoader = function (course_id) {
	var loadDiv = document.createElement("div"),
	titleDiv = YAHOO.util.Dom.get('id_' + course_id + "_title");
	
	loadDiv.id = 'id_' + course_id + "_load";
	loadDiv.className = 'liloader';
	titleDiv.appendChild(loadDiv);
};

sits_block.exit = function () {
	var confirmed;
	confirmed = confirm('Click OK to close the Cohorts and Groups interface');
	if (confirmed)
	{
		window.close();
	}
};

sits_block.display_add_module = function (moodle_course) {
	var child;
    child = window.open("./samis_user_interface_addm.php?moodle_course=" + moodle_course,"addmodule", "height=600,width=587,status=yes,toolbar=no,menubar=no,scrollbars=1,location=no");
};

sits_block.validate_sits_code_input = function(id) {
	var codeElement = YAHOO.util.Dom.get(id + '_code_input'),
	progPatt = /^[A-z]{4}\-[A-z]{3}\d{2}$/,
	unitPatt = /^[A-z]{2}\d{5}$/,
	sits_code = codeElement.value.toUpperCase();
	

	if (sits_code.match(unitPatt)){
		//Unit
		YAHOO.util.Dom.setAttribute(YAHOO.util.Dom.get(id + '_yos_div'), 'style', 'display:none;');
		YAHOO.util.Dom.setAttribute(YAHOO.util.Dom.get(id + '_period_div'), 'style', 'display:block;');
	} else if (sits_code.match(progPatt)){
		//Programme
		YAHOO.util.Dom.setAttribute(YAHOO.util.Dom.get(id + '_yos_div'), 'style', 'display:block;');
		YAHOO.util.Dom.setAttribute(YAHOO.util.Dom.get(id + '_period_div'), 'style', 'display:none;');
	} else {
		alert('Please enter SAMIS code in a valid Unit or Programme format');
		codeElement.focus();
	}
};

sits_block.add_module_to_mappings = function (courseid, formid) {

	var mappingXML, unenrol,
	codeElement = YAHOO.util.Dom.get(formid + '_code_input'),
	sits_code = codeElement.value.toUpperCase(),
	periodElement = YAHOO.util.Dom.get(formid + '_period'),
	yosElement = YAHOO.util.Dom.get(formid + '_yos'),
	dayElement = YAHOO.util.Dom.get(formid + '_day'),
	monElement = YAHOO.util.Dom.get(formid + '_month'),
	yearElement = YAHOO.util.Dom.get(formid + '_year'),
	acYearElement = YAHOO.util.Dom.get(formid + '_acyear'),	
	unenrolElement = YAHOO.util.Dom.get(formid + '_unenrol'),
	dateString = dayElement.value +  ' ' + monElement.value +  ' ' + yearElement.value,
	date = new Date(dateString),
	today = new Date(),
	dateStringToSend = date.getFullYear().toString() + '-' + (date.getMonth() + 1).toString() + '-' + date.getDate().toString(),
	progPatt = /^[A-z]{4}\-[A-z]{3}\d{2}$/,
	unitPatt = /^[A-z]{2}\d{5}$/;
	
	if (sits_code === '') {
		alert('Please specify a SAMIS cohort.');
		codeElement.focus();
		return;
	}
	
	if (sits_code.match(unitPatt)){
		//Unit
		type = "module";
	} else if (sits_code.match(progPatt)){
		//Programme
		type = "program";
	} else {
		alert('Please enter SAMIS code in a valid Unit or Programme format');
		codeElement.focus();
		return;
	}
	
	if (unenrolElement.value === 'specified' && date<today) {
	  alert("The date you entered is either today, or in the past - please enter a future date");
	  dayElement.focus();
	  return;
	}
	
	unenrol = unenrolElement.options[unenrolElement.selectedIndex].value;
	if(unenrol == "Sync"){
		unenrol = 'auto';
	}else{
		unenrol = unenrol.toLowerCase();
	}
	
	mappingXML = sits_block.createMappingXML(
			courseid,
			sits_code,
			type,
			acYearElement.options[acYearElement.selectedIndex].value,
			periodElement.options[periodElement.selectedIndex].value, 
			yosElement.options[yosElement.selectedIndex].value,
			unenrol, 
			dateStringToSend
	);
	sits_block.showAddingCohortLoader();
	sits_block.swapControlsForLoadMessage(courseid, 'Adding mapping - please wait');
	sits_block.cidForLastOperation = courseid;
	sits_block.asyncRequest('create_map', mappingXML);
};

sits_block.switchModuleType = function () {
	var periodselect, programyearselect, moduletype;
	
	periodselect = YAHOO.util.Dom.get('mod-programme');
	programyearselect = YAHOO.util.Dom.get('mod-period');
	moduletype = YAHOO.util.Dom.get('select_moduletype').value;

	if (moduletype === 'programme')
	{
		periodselect.disabled = false;
		programyearselect.disabled = true;			
	}
	else
	{
		periodselect.disabled = true;
		programyearselect.disabled = false;			
	}
};

sits_block.toggle_delete = function (remButton, mapId) {
	var mapElement = YAHOO.util.Dom.get('id_' + mapId),
	enrolElement = YAHOO.util.Dom.get('id_' + mapId + '_map_unenrol_type');
	
	if (remButton.value === 'Remove Cohort') {
		remButton.value = 'Restore Cohort';
		sits_block.setDatesDisabledAttribute(mapId, true);
		YAHOO.util.Dom.addClass(mapElement, 'disabled');
		enrolElement.disabled = true;
	} else {
		remButton.value = 'Remove Cohort';
		sits_block.setDatesDisabledAttribute(mapId, false);
		YAHOO.util.Dom.removeClass(mapElement, 'disabled');
		enrolElement.disabled = false;
	}	
};

sits_block.update_cohort_list = function (courseid) {
	var i, xml,
	mappingElement = YAHOO.util.Dom.get('select_mappings'),
	mappingsOptions = YAHOO.util.Dom.getChildren(mappingElement);
	
	for(i=0; i<mappingsOptions.length; i++) {
		mappingsOptions[i].parentNode.removeChild(mappingsOptions[i]);
	}
	//build XML for request
	xml = "<?xml version='1.0' standalone='yes'?>"; 
	xml += '<get_maps><course_id>' + courseid + '</course_id></get_maps>';
    //Kick off request
	sits_block.asyncRequest('get_map_ids_group', xml);
};

sits_block.update_group_list = function (courseid) {
	var i, xml,
	groupsElement = YAHOO.util.Dom.get('select_groups'),
	groupsOptions = YAHOO.util.Dom.getChildren(groupsElement);
	
	for(i=0; i<groupsOptions.length; i++) {
		groupsOptions[i].parentNode.removeChild(groupsOptions[i]);
	}
	//build XML for request
	xml = "<?xml version='1.0' standalone='yes'?>";
	xml += '<get_groups><course_id>' + courseid + '</course_id></get_groups>';
    //Kick off request
	sits_block.asyncRequest('get_groups', xml);
};

sits_block.set_group_options = function () {
	
	var selectElement;
	sits_block.hideGroupNoMaps();
	sits_block.showGroupLoading('Loading mapped cohorts - please wait');
	selectElement = YAHOO.util.Dom.get('grp_course');
	sits_block.update_cohort_list(selectElement.options[selectElement.selectedIndex].value);
	sits_block.update_group_list(selectElement.options[selectElement.selectedIndex].value);
};

sits_block.create_or_add_to_group = function () {
	var op, xml, i,
	mappingSelect = YAHOO.util.Dom.get('select_mappings'),
	groupElement,
	courseSelect = YAHOO.util.Dom.get('grp_course'),
	createRadioBtn = YAHOO.util.Dom.get('grp_radio_create'),
	courseid = courseSelect.options[courseSelect.selectedIndex].value;	
	
	xml = "<?xml version='1.0' standalone='yes'?>";
	xml += '<maps_to_group><course_id>' + courseid + '</course_id>';
	
	if (createRadioBtn.checked) {
		op = 'create_group';
		groupElement = YAHOO.util.Dom.get('groupname');
		if(groupElement.value === 'Enter group name'){
			alert("'Enter group name' is not allowed as a group name - please enter a different name");
			return false;
		}
		xml += '<group_name>' + groupElement.value + '</group_name>';
		sits_block.showGroupLoading('Creating group - please wait');
	} else {
		op = 'add_to_group';
		groupElement = YAHOO.util.Dom.get('select_groups');
		xml += '<group_id>' + groupElement.options[groupElement.selectedIndex].value + '</group_id>';
		sits_block.showGroupLoading('Adding selected cohorts to group - please wait');
	}	
	xml +='<maps>';
	for(i=0;i<mappingSelect.options.length; i++) {
		if (mappingSelect.options[i].selected) {
			xml += '<map_id>' + mappingSelect.options[i].id + '</map_id>';
		}
	}
	xml +='</maps>';
	xml += '</maps_to_group>';
	sits_block.asyncRequest(op, xml);
};

sits_block.update_add_group = function ()
{
	var bx_cohort, bx_group, btn_add;
	
	bx_cohort = YAHOO.util.Dom.get('listcohorts[]');
	bx_group = YAHOO.util.Dom.get('listgroups');
	btn_add = YAHOO.util.Dom.get('groupsubmit');
	if (bx_cohort.value === 'no mappings')
	{
		btn_add.disabled = true;
	}
	else
	{
		btn_add.disabled = false;
	}
};

sits_block.groupnameselect = function ()
{
	var bx, rd;
	
	bx = YAHOO.util.Dom.get('groupname');
	rd = YAHOO.util.Dom.get('grp_radio_create');
	if (bx.value === 'Enter group name')
	{
		bx.value = '';
	}
	rd.checked="checked";
};

sits_block.groupexistselect = function ()
{
	var rd;
	
	rd = YAHOO.util.Dom.get('grp_radio_exist');
	rd.checked="checked";
};

sits_block.switch_view = function (view)
{
	var groupElement = YAHOO.util.Dom.get('groups'),
	cohortElement = YAHOO.util.Dom.get('cohorts'),
	groupBtn = YAHOO.util.Dom.get('group_btn'),
	cohortBtn = YAHOO.util.Dom.get('cohort_btn');
	
	if (view === 'group') {
		YAHOO.util.Dom.setAttribute(groupElement, 'style', 'display:block;');
		YAHOO.util.Dom.setAttribute(cohortElement, 'style', 'display:none;');
		groupBtn.disabled=true;
		cohortBtn.disabled=false;
	} else {
		YAHOO.util.Dom.setAttribute(groupElement, 'style', 'display:none;');
		YAHOO.util.Dom.setAttribute(cohortElement, 'style', 'display:block;');
		groupBtn.disabled=false;
		cohortBtn.disabled=true;
	}
};

sits_block.enable_save = function (courseid)
{
	YAHOO.util.Dom.get('id_' + courseid + '_save').disabled = false;
	sits_block.changes[courseid] = true;
};

sits_block.mapValueChange = function (mapid)
{
	sits_block.mapValueHasChanged[mapid] = true;
};

sits_block.periodValueChange = function (periodid)
{
	sits_block.periodValueHasChanged[periodid] = true;
};


sits_block.disable_save = function (courseid)
{
	YAHOO.util.Dom.get('id_' + courseid + '_save').disabled = true;
	sits_block.changes[courseid] = false;
};

sits_block.view_course = function (course_id) {
	window.open('/blocks/sits/gui/views/enrols.php?id=' + course_id, '', 'height = 600px, width = 800px, scrollbars=yes');
};

sits_block.add_user = function () {
	var bucs_id, fieldElement, xml;
	
	fieldElement = YAHOO.util.Dom.get('bucs_id_input');
	bucs_id = fieldElement.value;
	if (sits_block.validate_bucs_id(bucs_id)) {
		xml = "<?xml version='1.0' standalone='yes'?>";
		xml += "<useradd>";
		xml += "<bucsid>" + bucs_id + "</bucsid>";
		xml += "</useradd>";
		sits_block.blurScreen('Adding User - please wait');
		sits_block.asyncRequest('add_user', xml);		
	} else {
		alert('The username you have entered is not in a BUCS username format - please enter a valid BUCS username');
	}
};

sits_block.sync_all_courses = function () {
	var confirmed;
	
	confirmed = confirm('If you are absolutely sure you want to sync Each and Every Course In Moodle, go ahead and confirm.');
	if (confirmed)
	{
		sits_block.blurScreen('Syncing All Courses - please wait');
		sits_block.asyncRequest('sync_all',"<?xml version='1.0' standalone='yes'?><sync_all></sync_all>");
	}
};

sits_block.filterCourses = function (searchString) {
	var i, titleString, parentElement, no_match = 0, match = 0; 
	messageElement = YAHOO.util.Dom.get('filter_message'),
	searchPattern = new RegExp(searchString, 'i'),
	courseTitles = YAHOO.util.Dom.getElementsByClassName('course_title');	
	
	for(i = 0; i < courseTitles.length; i++) {
		titleString = sits_block.getTextContent(courseTitles[i]);
		parentElement = courseTitles[i].parentNode;
		if (searchString === '') {
			YAHOO.util.Dom.setAttribute(parentElement, 'style', 'display:block;');
		} else {
			if (titleString.match(searchPattern) === null) {
				no_match++;
				YAHOO.util.Dom.setAttribute(parentElement, 'style', 'display:none;');
			} else {
				match++;
				YAHOO.util.Dom.setAttribute(parentElement, 'style', 'display:block;');
			}
		}		
	}
	if(searchString === ''){
		sits_block.setTextContent(messageElement, 'Enter part of a course name or idnumber to filter display');
	}else{
		if(no_match === courseTitles.length){
			sits_block.setTextContent(messageElement, 'Your search returned no courses');
		}else{
			if(match === 1){
				sits_block.setTextContent(messageElement, 'Your search returned ' + match.toString() + ' course');
			}else{
				sits_block.setTextContent(messageElement, 'Your search returned ' + match.toString() + ' courses');
			}
		}
	}
};

sits_block.populate_period_codes = function () {
	var periodDiv = YAHOO.util.Dom.get('period_code_div'),
	periodTable = YAHOO.util.Dom.get('period_code_table'),
	row = document.createElement('tr'),
	td1 = document.createElement('td'),
	td2 = document.createElement('td'),
	td3 = document.createElement('td'),
	td4 = document.createElement('td'),
	td5 = document.createElement('td'),
	xml = "<?xml version='1.0' standalone='yes'?><get_periods></get_periods>";
	
	periodTable.parentNode.removeChild(periodTable);
	periodTable = document.createElement('table');
	YAHOO.util.Dom.setAttribute(periodTable, 'id', 'period_code_table');	
	sits_block.setTextContent(td1, 'Period Code');
	sits_block.setTextContent(td2, 'Academic Year');
	sits_block.setTextContent(td3, 'Start Date');
	sits_block.setTextContent(td4, 'End Date');
	sits_block.setTextContent(td5, 'Revert/Remove');
	row.appendChild(td1);	
	row.appendChild(td2);	
	row.appendChild(td3);	
	row.appendChild(td4);	
	row.appendChild(td5);	
	periodTable.appendChild(row);
	periodDiv.appendChild(periodTable);		
	sits_block.asyncRequest('get_periods', xml);
};

sits_block.appendPeriod = function (periodXML, added) {
	var id, revert, remove, acyearOptions, periodOptions, html,
	code = periodXML.getElementsByTagName("code")[0].firstChild.data,
	acyear = periodXML.getElementsByTagName("acyear")[0].firstChild.data,
	startDate = sits_block.loadDate(periodXML.getElementsByTagName("start")[0].firstChild.data),
	endDate = sits_block.loadDate(periodXML.getElementsByTagName("end")[0].firstChild.data),
	periodTable = YAHOO.util.Dom.get('period_code_table'),
	row = document.createElement('tr'),
	startDiv = document.createElement("div"),
	endDiv = document.createElement("div"),
	startYearOptions = sits_block.get_years(startDate),
	startMonthOptions = sits_block.get_months(startDate),
	startDayOptions = sits_block.get_days(startDate),
	endYearOptions = sits_block.get_years(endDate),
	endMonthOptions = sits_block.get_months(endDate),
	endDayOptions = sits_block.get_days(endDate),
	td1 = document.createElement('td'),
	td2 = document.createElement('td'),
	td3 = document.createElement('td'),
	td4 = document.createElement('td'),
	td5 = document.createElement('td');	
	
	if (added) {
		id = "new_" + sits_block.newPeriodCount;
		row.className = id;
		YAHOO.util.Dom.setAttribute(row, 'id', 'period_new_' + sits_block.newPeriodCount);
		periodOptions = sits_block.get_period_options();
		acyearOptions = sits_block.get_acyear_options();
		remove = document.createElement('input');
		YAHOO.util.Dom.setAttribute(remove, 'type', 'submit');
		YAHOO.util.Dom.setAttribute(remove, 'value', 'Remove');
		YAHOO.util.Dom.setAttribute(remove, 'onclick', 'sits_block.remove_element("period_new_' + sits_block.newPeriodCount + '")');		
		html = '<select id = "period_code_select_' + id + '">';
		html += periodOptions;
		html +='</select>';		
		td1.innerHTML = html;		
		html = '<select id = "period_acyear_select_' + id + '">';
		html += acyearOptions;
		html +='</select>';		
		td2.innerHTML = html;
		row.style.cssText = 'background-color: #BAD897;';
		sits_block.newPeriodCount++;
		td5.appendChild(remove);
	} else {
		id = periodXML.getElementsByTagName("id")[0].firstChild.data;
		row.className = id;
		revert = document.createElement('input');
		if (periodXML.getElementsByTagName("revert")[0].firstChild.data === '1') {
			YAHOO.util.Dom.setAttribute(revert, 'checked', 'yes');
		}
		YAHOO.util.Dom.setAttribute(revert, 'type', 'checkbox');
		YAHOO.util.Dom.setAttribute(revert, 'id', 'id_p_revert_' + id);
		YAHOO.util.Dom.setAttribute(revert, 'onchange', 'sits_block.periodValueChange(' + id + ')');
		sits_block.periodValueChange(' + id + ');		
		sits_block.setTextContent(td1, code);
		YAHOO.util.Dom.setAttribute(td1, 'id', 'id_p_code_' + id);
		sits_block.setTextContent(td2, acyear);
		YAHOO.util.Dom.setAttribute(td2, 'id', 'id_p_acyear_' + id);
		YAHOO.util.Dom.setAttribute(row, 'id', 'period_id_' + id);
		td5.appendChild(revert);
		if (sits_block.isEven(sits_block.periodAppendCount)) {
			row.style.cssText = 'background-color: #DFDFDF;';
		} else {
			row.style.cssText = 'background-color: #EFEFEF;';
		}
	}	
	html = '<div id = "id_' + id + '_period">';
	html += '<select class="day" id="id_p_start_day_' + id + '" class="map_select" onchange="sits_block.enable_period_save(); sits_block.periodValueChange(' + "'" + id + "'" + ')">';
	html += startDayOptions;
	html += '</select>';
	html += '<select class="month" id="id_p_start_month_' + id + '"  class="map_select" onchange="sits_block.enable_period_save(); sits_block.periodValueChange('+ "'" +id+ "'" +')">';
	html += startMonthOptions;
	html += '</select>';
	html += '<select class="year" id="id_p_start_year_' + id + '" class="map_select" onchange="sits_block.enable_period_save(); sits_block.periodValueChange('+ "'" +id+ "'" +')">';
	html += startYearOptions;
	html += '</select>';
	html += '</div>';	
	startDiv.innerHTML = html;	
	html = '<div id = "id_' + id + '_period">';
	html += '<select class="day" id="id_p_end_day_' + id + '" class="map_select" onchange="sits_block.enable_period_save(); sits_block.periodValueChange('+ "'" +id+ "'" +')">';
	html += endDayOptions;
	html += '</select>';
	html += '<select class="month" id="id_p_end_month_' + id + '"  class="map_select" onchange="sits_block.enable_period_save(); sits_block.periodValueChange('+ "'" +id+ "'" +')">';
	html += endMonthOptions;
	html += '</select>';
	html += '<select class="year" id="id_p_end_year_' + id + '" class="map_select" onchange="sits_block.enable_period_save(); sits_block.periodValueChange(' + "'" + id + "'" + ')">';
	html += endYearOptions;
	html += '</select>';
	html += '</div>';	
	endDiv.innerHTML = html;
	row.appendChild(td1);	
	row.appendChild(td2);	
	td3.appendChild(startDiv);
	row.appendChild(td3);	
	td4.appendChild(endDiv);
	row.appendChild(td4);		
	row.appendChild(td5);	
	periodTable.appendChild(row);	
	sits_block.periodAppendCount++;
};

sits_block.appendNoAlteredPeriods = function () {

};

sits_block.add_period_alteration = function () {
	var period_xml = sits_block.create_period_xml('null', 'null', '1970-01-01', '1970-01-01', '0', 'new', true);
	sits_block.appendPeriod(sits_block.loadXMLString(period_xml), true);
};

sits_block.create_period_xml = function (code, academic_year, start, end, revert, id, new_alter, doctype) {	
	var XML;
	if (doctype) {
		XML = "<?xml version='1.0' standalone='yes'?>";
		XML += '<period_alter>';
	} else {
		XML = '<period_alter>';
	}	
	XML += '<id>' + id + '</id>';
	XML += '<code>' + code + '</code>';
	XML += '<acyear>' + academic_year + '</acyear>';
	XML += '<start>' + start + '</start>';
	XML += '<end>' + end + '</end>';	
	XML += '<revert>' + revert + '</revert>';
	XML += '<new_alter>' + new_alter + '</new_alter>';
	XML += '</period_alter>';
	return XML;	
};

sits_block.period_save = function () {
	var xml,
	saveConfirm;
	xml = sits_block.create_batch_period_xml();
	saveConfirm = confirm('Please be aware these changes might potentially enrol or unenrol thousands of students.\n\nPress OK to continue, or cancel to review your changes.');
	if (saveConfirm) {
		if (sits_block.validate_period_batch_xml(xml)) {
			sits_block.periodSwapLoadForControls();
			sits_block.asyncRequest('save_periods', xml);
		}
	}
};

sits_block.validate_period_batch_xml = function (xmlString) {
	var i, checkId, id, start, end, code, acyear, codeCheck, acyearCheck, periodTable, periodTableRows, row, codeCheckElement, acyearCheckElement,
	xml = sits_block.loadXMLString(xmlString),
	xmlElements = xml.getElementsByTagName("period_alter");
	
	if (xmlElements.length > 0) {
		periodTable = YAHOO.util.Dom.get('period_code_table');		
		for (i=0;i<xmlElements.length;i++) {
			id = xmlElements[i].getElementsByTagName('id')[0].firstChild.data;
			code = xmlElements[i].getElementsByTagName('code')[0].firstChild.data;
			acyear = xmlElements[i].getElementsByTagName('acyear')[0].firstChild.data;
			start = sits_block.loadDate(xmlElements[i].getElementsByTagName('start')[0].firstChild.data);
			end = sits_block.loadDate(xmlElements[i].getElementsByTagName('end')[0].firstChild.data);
			if (end < start) {
				alert('The end date for period code ' + code + ', academic year ' + acyear + ' is before its start date - please ammend and try again');
				return false;
			}
			periodTableRows = periodTable.getElementsByTagName("tr");
			for (i = 1; i<periodTableRows.length; i++) {
				row = periodTableRows[i];
				checkId = row.className;
				if (checkId.substring(0,3) !== 'new') {
					codeCheckElement = YAHOO.util.Dom.get('id_p_code_' + checkId);
					acyearCheckElement = YAHOO.util.Dom.get('id_p_acyear_' + checkId);
					codeCheck = sits_block.getTextContent(codeCheckElement);
				    acyearCheck = sits_block.getTextContent(acyearCheckElement); 
				} else {
					codeCheckElement = YAHOO.util.Dom.get('period_code_select_' + checkId);
				    acyearCheckElement = YAHOO.util.Dom.get('period_acyear_select_' + checkId);
				    codeCheck = codeCheckElement.value;
				    acyearCheck = acyearCheckElement.value;
				}
				if (code === codeCheck && acyear === acyearCheck && id !== checkId) {
					alert('You appear to have a duplicate entry for the period with code ' + code + ', academic year ' + acyear + ' - please ammend and try again');
					return false;
				}
			}
		}
	}
	return true;
};

sits_block.enable_period_save = function () {
	
};

sits_block.reset_sync_flag = function (){
	var xml,
	saveConfirm;
	xml = "<?xml version='1.0' standalone='yes'?>";
	xml += '<reset_sync_flag></reset_sync_flag>';
	saveConfirm = confirm('If you are ABSOLUTELY CONVINCED the problem that caused the flag to not reset automatically has been rectified, press OK to reset it now.\n\nOtherwise, press cancel, and fix the problem.');
	if (saveConfirm) {
		sits_block.asyncRequest('reset_sync_flag', xml);
	}
};

sits_block.addModuleClick = function (courseid) {
	sits_block.add_mapping(courseid);
};

sits_block.add_mapping = function (courseid) {
	if(sits_block.incompleteForm[courseid] === true){
		alert('You already have an Add Cohort form open for this course - please complete that one and press Save Changes.');
	}else{
		sits_block.appendNewMappingFormToPage(courseid);
		sits_block.incompleteForm[courseid] = true;
	}
};

sits_block.appendNewMappingFormToPage = function (courseid) {
	
	date = new Date(),
	periodOptions = sits_block.get_period_options(),
	acyearOptions = sits_block.get_acyear_options(),
	yosOptions = sits_block.get_yos_options(),
	year_options = sits_block.get_years(date),
	month_options = sits_block.get_months(date),
	day_options = sits_block.get_days(date);
	
	mapFormDiv = document.createElement('div');
	
	mapFormDiv.style.cssText = 'background-color: #eeeeee; margin-top: 4px;';
	
	mapFormDiv.id = 'id_' + courseid + '_new_map';
	mapFormDiv.className = 'new_mapping';
	

	html = '<div id = "' + mapFormDiv.id + '_code_input_div"class="new_map_form_element">';
		html += '<div class="new_map_form_label">SAMIS Code:</div>';
		html += '<div class="new_map_form_input"><input id = "' + mapFormDiv.id + '_code_input" style="width: 10em;" id="sits_code" type="text" value="" size="13" onchange="sits_block.validate_sits_code_input(' + "'" + mapFormDiv.id + "'" + ')"/></div>';
	html += '</div>';

	html += '<div id="' + mapFormDiv.id + '_acyear_div" class="new_map_form_element">';	
		html += '<div class="new_map_form_label">Academic Year: </div>';
		html += '<div class="new_map_form_input">';
			html += '<select id="' + mapFormDiv.id + '_acyear" class="map_select">';
			html += acyearOptions;
			html += '</select>';
		html += '</div>';
	html += '</div>';
	
	html += '<div id="' + mapFormDiv.id + '_period_div" class="new_map_form_element">';
		html += '<div class="new_map_form_label">Period Code: </div>';
		html += '<div class="new_map_form_input">';
			html += '<select id="' + mapFormDiv.id + '_period" class="map_select">';
			html += periodOptions;
			html += '</select>';
		html += '</div>';	
	html += '</div>';
	
	html += '<div id="' + mapFormDiv.id + '_yos_div" class="new_map_form_element">';	
	html += '<div class="new_map_form_label">Year of Study: </div>';
		html += '<div class="new_map_form_input">';
			html += '<select id="' + mapFormDiv.id + '_yos" class="map_select">';
			html += yosOptions;
			html += '</select>';
		html += '</div>';
	html += '</div>';
	
	html += '<div id="' + mapFormDiv.id + '_unenrol_div" class="new_map_form_element">';
		html += '<div class="new_map_form_label">Unenrol Method: </div>';
		html += '<div class="new_map_form_input">';
			html += '<select id="' + mapFormDiv.id + '_unenrol" class="map_select" onchange="sits_block.new_map_enrol_change(' + "'" + mapFormDiv.id + "'" + ')">';
			html += '<option>Sync</option>';
			html += '<option>Specified</option>';
			html += '<option>Manual</option>';
			html += '</select>';
		html += '</div>';
	html += '</div>';
	
	html += '<div id="' + mapFormDiv.id + '_date_div" class="new_map_form_element" style="display: none;">';
		html += '<div class="new_map_form_label">Date: </div>';
		html += '<div class="new_map_form_input">';
			html += '<select class="day" id="' + mapFormDiv.id + '_day" class="map_select">';
			html += day_options;
			html += '</select>';
			html += '<select class="month" id="' + mapFormDiv.id + '_month"  class="map_select">';
			html += month_options;
			html += '</select>';
			html += '<select class="year" id="' + mapFormDiv.id + '_year" class="map_select">';
			html += year_options;
			html += '</select>';
		html += '</div>';
	html += '</div>';
	
	html += '<br/>';
	html += '<div class="new_map_form_element">';
		html += '<div class="new_map_form_label">&nbsp</div>';
		html += '<div class="new_map_form_input" style="margin-right: 5px;">';
		html += '<input type="submit" class="map_submit" id="id_' + mapFormDiv.id + '_map_save"  value ="Create Mapping" onclick="sits_block.add_module_to_mappings(' + courseid + ",'" + mapFormDiv.id + "'" + ')"/>';
	html += '</div>';
		html += '<div class="new_map_form_input">';
			html += '<input type="submit" class="map_submit" id="id_' + mapFormDiv.id + '_map_remove"  value ="Remove" onclick="sits_block.remove_new_map_form(' + courseid + ')"/>';
		html += '</div>';
	html += '</div>';

	mapFormDiv.innerHTML = html;
	
	YAHOO.util.Dom.get('id_' + courseid + '_new_map_form').appendChild(mapFormDiv);
	
};

sits_block.new_map_enrol_change = function (id) {
	if(1 === YAHOO.util.Dom.get(id + '_unenrol').selectedIndex){
		YAHOO.util.Dom.setAttribute(YAHOO.util.Dom.get(id + '_date_div'), 'style', 'display:block;');		
	} else {
		YAHOO.util.Dom.setAttribute(YAHOO.util.Dom.get(id + '_date_div'), 'style', 'display:none;');
	}
};

sits_block.admin_init = function () {
	sits_block.populate_period_codes();
};

sits_block.user_init = function () {	
    //sits_block.switch_view('cohort');
    //sits_block.toggle_dates('add');
    //sits_block.set_group_options();
};

sits_block.course_init = function (courseid) {
	sits_block.loadMappingsForCourse(courseid);
    //sits_block.switch_view('cohort');
    //sits_block.toggle_dates('add');
    //sits_block.set_group_options();
};

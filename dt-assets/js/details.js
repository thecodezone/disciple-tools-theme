"use strict";
jQuery(document).ready(function($) {
  let post_id = window.detailsSettings.post_id
  let post_type = window.detailsSettings.post_type
  let post = window.detailsSettings.post_fields
  let field_settings = window.detailsSettings.post_settings.fields
  window.post_type_fields = field_settings
  let rest_api = window.API
  let typeaheadTotals = {}
  let current_record = -1;
  let next_record    = -1;
  let records_list   = window.SHAREDFUNCTIONS.get_json_cookie('records_list');


  window.masonGrid = $('.grid') // responsible for resizing and moving the tiles

  const detailsBarCreatedOnElements = document.querySelectorAll('.details-bar-created-on')
  detailsBarCreatedOnElements.forEach((element) => {
    const postDate = post.post_date.timestamp
    const formattedDate = window.SHAREDFUNCTIONS.formatDate(postDate)
    element.innerHTML = window.lodash.escape( window.detailsSettings.translations.created_on.replace('%s', formattedDate) )
  })

  $('input.text-input').change(function(){
    const id = $(this).attr('id')
    const val = $(this).val()
    if ( $(this).prop('required') && val === ''){
      return;
    }
    $(`#${id}-spinner`).addClass('active')
    rest_api.update_post(post_type, post_id, { [id]: val }).then((newPost)=>{
      $(`#${id}-spinner`).removeClass('active')
      $( document ).trigger( "text-input-updated", [ newPost, id, val ] );
    }).catch(handleAjaxError)
  })
  $('.dt_textarea').change(function(){
    const id = $(this).attr('id')
    const val = $(this).val()
    $(`#${id}-spinner`).addClass('active')
    rest_api.update_post(post_type, post_id, { [id]: val }).then((newPost)=>{
      $(`#${id}-spinner`).removeClass('active')
      $( document ).trigger( "textarea-updated", [ newPost, id, val ] );
    }).catch(handleAjaxError)
  })

  $('button.dt_multi_select').on('click',function () {
    let fieldKey = $(this).data("field-key")
    let optionKey = $(this).attr('id')
    let fieldValue = {}
    let data = {}
    let field = jQuery(`[data-field-key="${fieldKey}"]#${optionKey}`)
    field.addClass("submitting-select-button")
    let action = "add"
    if (field.hasClass("selected-select-button")){
      fieldValue.values = [{value:optionKey,delete:true}]
      action = "delete"
    } else {
      field.removeClass("empty-select-button")
      field.addClass("selected-select-button")
      fieldValue.values = [{value:optionKey}]
    }
    data[optionKey] = fieldValue
    $(`#${fieldKey}-spinner`).addClass('active')
    rest_api.update_post(post_type, post_id, {[fieldKey]: fieldValue}).then((resp)=>{
      $(`#${fieldKey}-spinner`).removeClass('active')
      field.removeClass("submitting-select-button selected-select-button")
      field.blur();
      field.addClass( action === "delete" ? "empty-select-button" : "selected-select-button");
      $( document ).trigger( "dt_multi_select-updated", [ resp, fieldKey, optionKey, action ] );
    }).catch(err=>{
      field.removeClass("submitting-select-button selected-select-button")
      field.addClass( action === "add" ? "empty-select-button" : "selected-select-button")
      handleAjaxError(err)
    })
  })


  $('.dt_date_picker').datepicker({
    constrainInput: false,
    dateFormat: 'yy-mm-dd',
    onClose: function (date) {
      date = window.SHAREDFUNCTIONS.convertArabicToEnglishNumbers(date);

      if (!$(this).val()) {
        date = " ";//null;
      }
      let id = $(this).attr('id')
      $(`#${id}-spinner`).addClass('active')
      rest_api.update_post( post_type, post_id, { [id]: moment.utc(date).unix() }).then((resp)=>{
        $(`#${id}-spinner`).removeClass('active')
        if (this.value) {
          this.value = window.SHAREDFUNCTIONS.formatDate(resp[id]["timestamp"]);
        }
        $( document ).trigger( "dt_date_picker-updated", [ resp, id, date ] );
      }).catch(handleAjaxError)
    },
    changeMonth: true,
    changeYear: true,
    yearRange: "1900:2050",
  }).each(function() {
    if (this.value && moment.unix(this.value).isValid()) {
      this.value = window.SHAREDFUNCTIONS.formatDate(this.value);
    }
  })


  let mcleardate = $(".clear-date-button");
  mcleardate.click(function() {
    let input_id = this.dataset.inputid;
    $(`#${input_id}`).val("");
    let date = null;
    $(`#${input_id}-spinner`).addClass('active')
    rest_api.update_post(post_type, post_id, { [input_id]: date }).then((resp) => {
      $(`#${input_id}-spinner`).removeClass('active')
      $(document).trigger("dt_date_picker-updated", [resp, input_id, date]);

    }).catch(handleAjaxError)
  });

  $('select.select-field').change(e => {
    const id = $(e.currentTarget).attr('id')
    const val = $(e.currentTarget).val()
    $(`#${id}-spinner`).addClass('active')

    rest_api.update_post(post_type, post_id, { [id]: val }).then(resp => {
      $(`#${id}-spinner`).removeClass('active')
      $( document ).trigger( "select-field-updated", [ resp, id, val ] );
      if ( $(e.currentTarget).hasClass( "color-select")){
        $(`#${id}`).css("background-color", window.lodash.get(window.detailsSettings, `post_settings.fields[${id}].default[${val}].color`) )
      }
    }).catch(handleAjaxError)
  })

  $('input.number-input').on("blur", function(){
    const id = $(this).attr('id')
    const val = $(this).val()
    $(`#${id}-spinner`).addClass('active')
    rest_api.update_post(post_type, post_id, { [id]: val }).then((resp)=>{
      $(`#${id}-spinner`).removeClass('active')
      $( document ).trigger( "number-input-updated", [ resp, id, val ] );
    }).catch(handleAjaxError)
  })

  $('.dt_contenteditable').on('blur', function(){
    const id = $(this).attr('id')
    let val = $(this).text();
    if ( id === "title" && val === '' ){
      return;
    }
    rest_api.update_post(post_type, post_id, { [id]: val }).then((resp)=>{
      $( document ).trigger( "contenteditable-updated", [ resp, id, val ] );
    }).catch(handleAjaxError)
  })

  // Clicking the plus sign next to the field label
  $('button.add-button').on('click', e => {
    const field = $(e.currentTarget).data('list-class')
    const $list = $(`#edit-${field}`)

    $list.append(`<div class="input-group">
            <input type="text" data-field="${window.lodash.escape( field )}" class="dt-communication-channel input-group-field" dir="auto" />
            <div class="input-group-button">
            <button class="button alert input-height delete-button-style channel-delete-button delete-button new-${window.lodash.escape( field )}" data-key="new" data-field="${window.lodash.escape( field )}">&times;</button>
            </div></div>`)
  })
  $(document).on('click', '.channel-delete-button', function(){
    let field = $(this).data('field')
    let key = $(this).data('key')
    let update = { delete:true }
    if ( key === 'new' ){
      $(this).parent().remove()
    } else if ( key ){
      $(`#${field}-spinner`).addClass('active')
      update["key"] = key;
      API.update_post(post_type, post_id, { [field]: [update]}).then((updatedContact)=>{
        $(this).parent().parent().remove()
        let list = $(`#edit-${field}`)
        if ( list.children().length === 0 ){
          list.append(`<div class="input-group">
            <input type="text" data-field="${window.lodash.escape( field )}" class="dt-communication-channel input-group-field" dir="auto" />
            </div>`)
        }
        $(`#${field}-spinner`).removeClass('active')
        post = updatedContact
        resetDetailsFields()
      }).catch(handleAjaxError)
    }
  })

  $(document).on('blur', 'input.dt-communication-channel', function(){
    let field_key = $(this).data('field')
    let value = $(this).val()
    let id = $(this).attr('id')
    let update = { value }
    if ( id ) {
      update["key"] = id;
    }
    $(`#${field_key}-spinner`).addClass('active')
    API.update_post(post_type, post_id, { [field_key]: [update]}).then((updatedContact)=>{
      $(`#${field_key}-spinner`).removeClass('active')
      let key = window.lodash.last(updatedContact[field_key]).key
      $(this).attr('id', key)
      if ( $(this).next('div.input-group-button').length === 1 ) {
        $(this).parent().find('.channel-delete-button').data('key', key)
      } else {
        $(this).parent().append(`<div class="input-group-button">
            <button class="button alert delete-button-style input-height channel-delete-button delete-button" data-key="${window.lodash.escape( key )}" data-field="${window.lodash.escape( field_key )}">&times;</button>
        </div>`)
      }
      post = updatedContact
      resetDetailsFields()
    }).catch(handleAjaxError)
  })

  $( document ).on( 'select-field-updated', function (e, newContact, id, val) {
  })

  $( document ).on( 'text-input-updated', function (e, newContact, id, val){
    if ( id === "name" ){
      $("#title").html(window.lodash.escape(val))
      $("#second-bar-name").text(window.lodash.escape(val))
    }
  })

  $( document ).on( 'contenteditable-updated', function (e, newContact, id, val){
    if ( id === "title" ){
      $("#name").val(window.lodash.escape(val))
      $("#second-bar-name").text(window.lodash.escape(val))
    }
  })

  $( document ).on( 'dt_date_picker-updated', function (e, newContact, id, date){
  })

  $( document ).on( 'dt_multi_select-updated', function (e, newContact, fieldKey, optionKey, action) {
  })

  $( document ).on( 'dt_record_updated', function (e, response, request ){
    post = response;
    resetDetailsFields()
    record_updated(window.lodash.get(response, "requires_update", false));

  })



  /**
   * Update Needed
   */
  $('.update-needed.dt-switch').change(function () {
    let updateNeeded = $(this).is(':checked')
    API.update_post( post_type, post_id, {"requires_update":updateNeeded}).then(resp=>{
      post = resp
    })
  })



  $('.show-details-section').on( "click", function (){
    $('#details-section').toggle()
    $('#show-details-edit-button').toggle()
    $(`#details-section .typeahead__query input`).each((i, element)=>{
      let field_key = $(element).data("field")
      if ( Typeahead[`.js-typeahead-${field_key}`]){
        Typeahead[`.js-typeahead-${field_key}`].adjustInputSize()
      }
    })
  })

  /**
   * user select typeahead
   */
  $('.dt_user_select').each((key, el)=>{
    let field_key = $(el).attr('id')
    let user_input = $(`.js-typeahead-${field_key}`)
    $.typeahead({
      input: `.js-typeahead-${field_key}`,
      minLength: 0,
      maxItem: 0,
      accent: true,
      searchOnFocus: true,
      source: TYPEAHEADS.typeaheadUserSource(),
      templateValue: "{{name}}",
      template: function (query, item) {
        return `<div class="assigned-to-row" dir="auto">
          <span>
              <span class="avatar"><img style="vertical-align: text-bottom" src="{{avatar}}"/></span>
              ${window.lodash.escape( item.name )}
          </span>
          ${ item.status_color ? `<span class="status-square" style="background-color: ${window.lodash.escape(item.status_color)};">&nbsp;</span>` : '' }
          ${ item.update_needed && item.update_needed > 0 ? `<span>
            <img style="height: 12px;" src="${window.lodash.escape( window.wpApiShare.template_dir )}/dt-assets/images/broken.svg"/>
            <span style="font-size: 14px">${window.lodash.escape(item.update_needed)}</span>
          </span>` : '' }
        </div>`
      },
      dynamic: true,
      hint: true,
      emptyTemplate: window.lodash.escape(window.wpApiShare.translations.no_records_found),
      callback: {
        onClick: function(node, a, item){
          API.update_post(post_type, post_id, {[field_key]: 'user-' + item.ID}).then(function (response) {
            window.lodash.set(post, field_key, response[field_key])
            user_input.val(post[field_key].display)
            user_input.blur()
          }).catch(err => { console.error(err) })
        },
        onResult: function (node, query, result, resultCount) {
          let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
          $(`#${field_key}-result-container`).html(text);
        },
        onHideLayout: function () {
          $(`.${field_key}-result-container`).html("");
        },
        onReady: function (node) {
          //if the input is disabled don't allow clicks on the cancel button.
          if($(node).attr('disabled') == 'disabled') {
           let cancelButton = $(`#${el.id} .typeahead__cancel-button`);
           cancelButton.css('pointerEvents','none');
         }
          if (window.lodash.get(post,  `${field_key}.display`)){
            $(`.js-typeahead-${field_key}`).val(post[field_key].display)
          }
        }
      },
    });
    $(`.search_${field_key}`).on('click', function () {
      user_input.val("")
      user_input.trigger('input.typeahead')
      user_input.focus()
    })
  })


  $('.dt_typeahead').each((key, el)=>{
    let div_id = $(el).attr('id')
    let field_id = $(`#${div_id} input`).data('field')
    let listing_post_type = window.lodash.get(window.detailsSettings.post_settings.fields[field_id], "post_type", 'contacts')
    $.typeahead({
      input: `.js-typeahead-${field_id}`,
      minLength: 0,
      accent: true,
      maxItem: 30,
      searchOnFocus: $(el).hasClass('disabled') ? false : true,
      template: window.TYPEAHEADS.contactListRowTemplate,
      matcher: function (item) {
        return parseInt(item.ID) !== parseInt(post_id)
      },
      source: window.TYPEAHEADS.typeaheadPostsSource(listing_post_type, {field_key:field_id}),
      display: ["name", "label"],
      templateValue: function() {
          if (this.items[this.items.length - 1].label) {
            return "{{label}}"
          } else {
            return "{{name}}"
          }
      },
      dynamic: true,
      multiselect: {
        matchOn: ["ID"],
        data: function () {
          return (post[field_id] || [] ).map(g=>{
            return {ID:g.ID, name:g.post_title, label: g.label}
          })
        },
        callback: {
          onCancel: function (node, item) {
            $(`#${field_id}-spinner`).addClass('active')
            API.update_post(post_type, post_id, {[field_id]: {values:[{value:item.ID, delete:true}]}}).then(()=>{
              $(`#${field_id}-spinner`).removeClass('active')
            }).catch(err => { console.error(err) })
          }
        },
        href: function (item) {
          if (listing_post_type === 'peoplegroups') {
            return null;
          } else {
            return window.wpApiShare.site_url + `/${listing_post_type}/${item.ID}`
          }
        }
      },
      callback: {
        onReady: function(node) {
          //if the input is disabled don't allow clicks on the cancel button.
           if($(node).attr('disabled') == 'disabled') {
            let cancelButton = $(`#${el.id} .typeahead__cancel-button`);
            cancelButton.css('pointerEvents','none');
          }
        },
        onClick: function(node, a, item, event){
          $(`#${field_id}-spinner`).addClass('active')
          API.update_post(post_type, post_id, {[field_id]: {values:[{"value":item.ID}]}}).then(new_post=>{
            $(`#${field_id}-spinner`).removeClass('active')
            $( document ).trigger( "dt-post-connection-added", [ new_post, field_id ] );
          }).catch(err => { console.error(err) })
          this.addMultiselectItemLayout(item)
          event.preventDefault()
          this.hideLayout();
          this.resetInput();
          masonGrid.masonry('layout')
        },
        onResult: function (node, query, result, resultCount) {
          let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
          $(`#${field_id}-result-container`).html(text);
        },
        onHideLayout: function (event, query) {
          if ( !query ){
            $(`#${field_id}-result-container`).empty()
          }
          masonGrid.masonry('layout')
        },
        onShowLayout (){
          masonGrid.masonry('layout')
        }
      }
    })
  })

  //multi_select typeaheads
  for (let input of $(".multi_select .typeahead__query input")) {
    let field = $(input).data('field')
    let typeahead_name = `.js-typeahead-${field}`

    if (window.Typeahead[typeahead_name]) {
      return
    }

    let source_data =  { data: [] }
    let field_options = window.lodash.get(field_settings, `${field}.default`, {})
    if ( Object.keys(field_options).length > 0 ){
      window.lodash.forOwn(field_options, (val, key)=>{
        if ( !val.deleted ){
          source_data.data.push({
            key: key,
            name:key,
            value: val.label || key
          })
        }
      })
    } else {
      source_data = {
        [field]: {
          display: ["value"],
          ajax: {
            url: window.wpApiShare.root + `dt-posts/v2/${post_type}/multi-select-values`,
            data: {
              s: "{{query}}",
              field
            },
            beforeSend: function (xhr) {
              xhr.setRequestHeader('X-WP-Nonce', window.wpApiShare.nonce);
            },
            callback: {
              done: function (data) {
                return (data || []).map(tag => {
                  let label = window.lodash.get(field_options, tag + ".label", tag)
                  return {value: label, key: tag}
                })
              }
            }
          }
        }
      }
    }
    $.typeahead({
      input: `.js-typeahead-${field}`,
      minLength: 0,
      maxItem: 20,
      searchOnFocus: true,
      template: function (query, item) {
        return `<span>${window.lodash.escape(item.value)}</span>`
      },
      source: source_data,
      display: "value",
      templateValue: "{{value}}",
      dynamic: true,
      multiselect: {
        matchOn: ["key"],
        data: function (){
          return (post[field] || [] ).map(g=>{
            return {key:g, value:window.lodash.get(field_settings, `${field}.default.${g}.label`, g)}
          })
        },
        callback: {
          onCancel: function (node, item, event) {
            $(`#${field}-spinner`).addClass('active')
            API.update_post(post_type, post_id, {[field]: {values:[{value:item.key, delete:true}]}}).then((new_post)=>{
              $(`#${field}-spinner`).removeClass('active')
              this.hideLayout();
              this.resetInput();
              $( document ).trigger( "dt_multi_select-updated", [ new_post, field ] );
            }).catch(err => { console.error(err) })
          }
        }
      },
      callback: {
        onClick: function(node, a, item, event){
          $(`#${field}-spinner`).addClass('active')
          API.update_post(post_type, post_id, {[field]: {values:[{"value":item.key}]}}).then(new_post=>{
            $(`#${field}-spinner`).removeClass('active')
            $( document ).trigger( "dt_multi_select-updated", [ new_post, field ] );
            this.addMultiselectItemLayout(item)
            event.preventDefault()
            this.hideLayout();
            this.resetInput();
          }).catch(err => { console.error(err) })
        },
        onResult: function (node, query, result, resultCount) {
          let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
          if ( Object.keys(field_options).length > 0 ){
            //adding the result text moves the input. The timeout helps keep the dropdown from closing as the user clicks and cursor moves away from the input.
            setTimeout( () => {
                $(`#${field}-result-container`).html(text);
              },200 );
          } else {
            $(`#${field}-result-container`).html(text);
          }
        },
        onHideLayout: function () {
          $(`#${field}-result-container`).html("");
        }
      }
    });
  }


  let connection_type = null
  //new record off a typeahead
  $('.create-new-record').on('click', function(){
    connection_type = $(this).data('connection-key');
    $('#create-record-modal').foundation('open');
    $('.js-create-record .error-text').empty();
    $(".js-create-record-button").attr("disabled", false).removeClass("alert")
    $(".reveal-after-record-create").hide()
    $(".hide-after-record-create").show()
    $(".js-create-record input[name=title]").val('')
    //create new record
  })
  $(".js-create-record").on("submit", function(e) {
    e.preventDefault();
    $(".js-create-record-button").attr("disabled", true).addClass("loading");
    let title = $(".js-create-record input[name=title]").val()
    if ( !connection_type){
      $(".js-create-record .error-text").text(
        "Something went wrong. Please refresh and try again"
      );
      return;
    }
    let update_field = connection_type;
    API.create_post( field_settings[update_field].post_type, {
      title,
      additional_meta: {
        created_from: post_id,
        add_connection: connection_type
      }
    }).then((newRecord)=>{
      $(".js-create-record-button").attr("disabled", false).removeClass("loading");
      $(".reveal-after-record-create").show()
      $("#new-record-link").html(`<a href="${window.lodash.escape( newRecord.permalink )}">${window.lodash.escape( title )}</a>`)
      $(".hide-after-record-create").hide()
      $('#go-to-record').attr('href', window.lodash.escape( newRecord.permalink ));
      $( document ).trigger( "dt-post-connection-created", [ post, update_field ] );
      if ( Typeahead[`.js-typeahead-${connection_type}`] ){
        Typeahead[`.js-typeahead-${connection_type}`].addMultiselectItemLayout({ID:newRecord.ID.toString(), name:title})
        masonGrid.masonry('layout')
      }
    })
    .catch(function(error) {
      $(".js-create-record-button").removeClass("loading").addClass("alert");
      $(".js-create-record .error-text").text(
        window.lodash.get( error, "responseJSON.message", "Something went wrong. Please refresh and try again" )
      );
      console.error(error);
    });
  })

  $('.dt_location_grid').each((key, el)=> {
    let field_id = $(el).data('id') || 'location_grid'
    $.typeahead({
      input: `.js-typeahead-${field_id}`,
      minLength: 0,
      accent: true,
      searchOnFocus: true,
      maxItem: 20,
      dropdownFilter: [{
        key: 'group',
        value: 'focus',
        template: window.lodash.escape(window.wpApiShare.translations.regions_of_focus),
        all: window.lodash.escape(window.wpApiShare.translations.all_locations),
      }],
      source: {
        focus: {
          display: "name",
          ajax: {
            url: window.wpApiShare.root + 'dt/v1/mapping_module/search_location_grid_by_name',
            data: {
              s: "{{query}}",
              filter: function () {
                return window.lodash.get(window.Typeahead[`.js-typeahead-${field_id}`].filters.dropdown, 'value', 'all')
              }
            },
            beforeSend: function (xhr) {
              xhr.setRequestHeader('X-WP-Nonce', window.wpApiShare.nonce);
            },
            callback: {
              done: function (data) {
                if (typeof typeaheadTotals!=="undefined") {
                  typeaheadTotals.field = data.total
                }
                return data.location_grid
              }
            }
          }
        }
      },
      display: "name",
      templateValue: "{{name}}",
      dynamic: true,
      multiselect: {
        matchOn: ["ID"],
        data: function () {
          return (post[field_id] || []).map(g => {
            return {ID: g.id, name: g.label}
          })

        }, callback: {
          onCancel: function (node, item) {
            API.update_post(post_type, post_id, {[field_id]: {values:[{value:item.ID, delete:true}]}})
            .catch(err => { console.error(err) })
          }
        }
      },
      callback: {
        onClick: function (node, a, item, event) {
          API.update_post(post_type, post_id, {[field_id]: {values:[{"value":item.ID}]}}).catch(err => { console.error(err) })
          this.addMultiselectItemLayout(item)
          event.preventDefault()
          this.hideLayout();
          this.resetInput();
          masonGrid.masonry('layout')
        },
        onReady() {
          this.filters.dropdown = {key: "group", value: "focus", template: window.lodash.escape(window.wpApiShare.translations.regions_of_focus)}
          this.container
          .removeClass("filter")
          .find("." + this.options.selector.filterButton)
          .html(window.lodash.escape(window.wpApiShare.translations.regions_of_focus));
        },
        onResult: function (node, query, result, resultCount) {
          resultCount = typeaheadTotals[field_id]
          let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
          $(`#${field_id}-result-container`).html(text);
        },
        onHideLayout: function () {
          $(`#${field_id}-result-container`).html("");
        }
      }
    });
  })

  /**
   * Follow
   */
  $('button.follow').on("click", function () {
    let following = !($(this).data('value') === "following")
    $(this).data("value", following ? "following" : "" )
    if ($(this).hasClass('mobile')) {
      $(this).html( following ? "<i class='fi-eye'></i>" : "<i class='fi-eye'></i>")
    } else {
      $(this).html( following ? "Following <i class='fi-eye'></i>" : "Follow <i class='fi-eye'></i>")
    }
    $(this).toggleClass( "hollow" )
    let update = {
      follow: {values:[{value:window.detailsSettings.current_user_id, delete:!following}]},
      unfollow: {values:[{value:window.detailsSettings.current_user_id, delete:following}]}
    }
    rest_api.update_post( post_type, post_id, update )
  })



  /**
   * Share
   */
  let shareTypeahead = null
  $('.open-share').on("click", function(){
    $('#share-contact-modal').foundation('open');
    if  (!shareTypeahead) {
      shareTypeahead = TYPEAHEADS.share(post_type, post_id )
    }
  })



  let build_task_list = ()=>{
    let tasks = window.lodash.sortBy(post.tasks || [], ['date']).reverse()
    let html = ``
    tasks.forEach(task=>{
      let task_done = ( task.category === "reminder" && task.value.notification === 'notification_sent' )
                      || ( task.category !== "reminder" && task.value.status === 'task_complete' )
      let show_complete_button = task.category !== "reminder" && task.value.status !== 'task_complete'
      let task_row = `<strong>${window.lodash.escape( moment(task.date).format("MMM D YYYY") )}</strong> `
      if ( task.category === "reminder" ){
        task_row += window.lodash.escape( window.detailsSettings.translations.reminder )
        if ( task.value.note ){
          task_row += ' ' + window.lodash.escape(task.value.note)
        }
      } else {
         task_row += window.lodash.escape(task.value.note || window.detailsSettings.translations.no_note )
      }
      html += `<li>
        <span style="${task_done ? 'text-decoration:line-through' : ''}">
        ${task_row}
        ${ show_complete_button ? `<button type="button" data-id="${window.lodash.escape(task.id)}" class="existing-task-action complete-task">${window.lodash.escape(window.detailsSettings.translations.complete).toLowerCase()}</button>` : '' }
        <button type="button" data-id="${window.lodash.escape(task.id)}" class="existing-task-action remove-task" style="color: red;">${window.lodash.escape(window.detailsSettings.translations.remove).toLowerCase()}</button>
      </li>`
    })
    if (!html ){
      $('#tasks-modal .existing-tasks').html(`<li>${window.lodash.escape(window.detailsSettings.translations.no_tasks)}</li>`)
    } else {
      $('#tasks-modal .existing-tasks').html(html)
    }

    $('.complete-task').on("click", function () {
      $('#tasks-spinner').addClass('active')
      let id = $(this).data('id')
      API.update_post(post_type, post_id, {
          "tasks": { values: [ { id, value: {status: 'task_complete'}, } ] }
      }).then(resp => {
        post = resp
        build_task_list()
        $('#tasks-spinner').removeClass('active')
      })
    })
    $('.remove-task').on("click", function () {
      $('#tasks-spinner').addClass('active')
      let id = $(this).data('id')
      API.update_post(post_type, post_id, {
          "tasks": { values: [ { id, delete: true } ] }
      }).then(resp => {
        post = resp
        build_task_list()
        $('#tasks-spinner').removeClass('active')
      })
    })
  }
  //open the create task modal
  $('.open-set-task').on( "click", function () {
    $('.js-add-task-form .error-text').empty();
    build_task_list()
    $('#tasks-modal').foundation('open');
  })
  $('#task-custom-text').on('click', function () {
    $('input:radio[name="task-type"]').filter('[value="custom"]').prop('checked', true);
  })
  $('#create-task-date').daterangepicker({
    "singleDatePicker": true,
    // "autoUpdateInput": false,
    // "timePicker": true,
    // "timePickerIncrement": 60,
    "locale": {
      "format": "YYYY/MM/DD",
      "separator": " - ",
      "daysOfWeek": window.SHAREDFUNCTIONS.get_days_of_the_week_initials(),
      "monthNames": window.SHAREDFUNCTIONS.get_months_labels(),
    },
    "firstDay": 1,
    "startDate": moment().add(1, "day"),
    "opens": "center",
    "drops": "down"
  });
  let task_note = $('#tasks-modal #task-custom-text')
  //submit the create task form
  $(".js-add-task-form").on("submit", function(e) {
    e.preventDefault();
    $("#create-task")
      .attr("disabled", true)
      .addClass("loading");
    let date = $('#create-task-date').data('daterangepicker').startDate
    let note = task_note.val()
    let task_type = $('#tasks-modal input[name="task-type"]:checked').val()
    API.update_post(post_type, post_id, {
      "tasks":{
        values: [
          {
            date: date.startOf('day').add(8, "hours").format(), //time 8am
            value: {note: note},
            category: task_type
          }
        ]
      }
    }).then( resp => {
      post = resp
      $("#create-task")
      .attr("disabled", false)
      .removeClass("loading");
      task_note.val('')
      $('#tasks-modal').foundation('close');
    }).catch(err => {
      $("#create-task")
      .attr("disabled", false)
      .removeClass("loading");
      $('.js-add-task-form .error-text').html(window.lodash.escape(window.lodash.get(err, "responseJSON.message")));
      console.error(err)
    })
  })

  /**
   * Favorite
   */
  function favorite_check(post_data) {
    if (post_data.favorite) {
      document.querySelectorAll('.button.favorite').forEach( function(button) {
          button.dataset.favorite = true
      })
      $('.button.favorite').addClass('selected');
    } else {
      document.querySelectorAll('.button.favorite').forEach( function(button) {
          button.dataset.favorite = false
      })
      $('.button.favorite').removeClass('selected');
    }
  }

  favorite_check(window.detailsSettings.post_fields);

  $('.button.favorite').on( "click", function () {
    var favorited = this.dataset.favorite
    var favoritedValue;
    if (favorited == "true") {
      this.dataset.favorite = false
      favoritedValue = false;
    } else if (favorited == "false") {
      this.dataset.favorite = true
      favoritedValue = true;
    }
    rest_api.update_post(post_type, post_id, {'favorite': favoritedValue}).then((new_post)=>{
      favorite_check(new_post);
    })
  })

  /**
   * Tags
   */
  $('.tags .typeahead__query input').each((key, input)=>{
    let field = $(input).data('field') || 'tags'
    let typeahead_name = `.js-typeahead-${field}`
    $.typeahead({
      input: typeahead_name,
      minLength: 0,
      maxItem: 20,
      searchOnFocus: true,
      source: {
        tags: {
          display: ["name"],
          ajax: {
            url: window.wpApiShare.root + `dt-posts/v2/${post_type}/multi-select-values`,
            data: {
              s: "{{query}}",
              field: field
            },
            beforeSend: function (xhr) {
              xhr.setRequestHeader('X-WP-Nonce', window.wpApiShare.nonce);
            },
            callback: {
              done: function (data) {
                return (data || []).map(tag => {
                  return {name: tag}
                })
              }
            }
          }
        }
      },
      display: "name",
      templateValue: "{{name}}",
      emptyTemplate: function(query) {
        const { addNewTagText, tagExistsText} = this.node[0].dataset
        if (this.comparedItems.includes(query)) {
          return tagExistsText.replace('%s', query)
        }
        const liItem = $('<li>')
        const button = $('<button>', {
          class: "button primary",
          text: addNewTagText.replace('%s', query),
        })
        const tag = this.query
        const addTag = addTagOnClick.bind(this)
        button.on("click", function () {
          addTag(field, tag)
        })
        liItem.append(button)
        return liItem
      },
      dynamic: true,
      multiselect: {
        matchOn: ["name"],
        data: function (){
          return (post[field] || [] ).map(t=>{
            return {name: t}
          })
        },
        callback: {
          onCancel: function (node, item, event) {
            $(`#${field}-spinner`).addClass('active')
            API.update_post(post_type, post_id, {[field]: {values:[{value:item.name, delete:true}]}}).then((new_post)=>{
              $(`#${field}-spinner`).removeClass('active')
              this.hideLayout();
              this.resetInput();
              $( document ).trigger( "dt_multi_select-updated", [ new_post, field ] );
            }).catch(err => { console.error(err) })
          }
        },
        href: function (item) {
          const postType = window.wpApiShare.post_type
          const query =  window.SHAREDFUNCTIONS.createCustomFilter('tags', [item.name])
          const labels = [{ id: `tags_${item.name}`, name: `Tags: ${item.name}`}]
          return window.SHAREDFUNCTIONS.create_url_for_list_query(postType, query, labels);
        },
      },
      callback: {
        onClick: function (node, a, item, event) {
          event.preventDefault()
          const addTag = addTagOnClick.bind(this)
          addTag(field, item.name)
        },
        onResult: function (node, query, result, resultCount) {
          let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
          $(`#${field}-result-container`).html(text);
          masonGrid.masonry('layout')
        },
        onHideLayout: function () {
          $(`#${field}-result-container`).html("");
          masonGrid.masonry('layout')
        },
        onShowLayout() {
          masonGrid.masonry('layout')
        }
      }
    });
  })

  $(".create-new-tag").on("click", function () {
    let field = $(this).data("field");
    $("#create-tag-modal").data("field", field)

  });
  $("#create-tag-return").on("click", function () {
    let field = $("#create-tag-modal").data("field");
    let tag = $("#new-tag").val()
    Typeahead['.js-typeahead-' + field].addMultiselectItemLayout({name: tag})
    API.update_post(post_type, post_id, {[field]: {values: [{value: tag}]}})
  })

  function addTagOnClick(field, tag) {
    $(`#${field}-spinner`).addClass('active')
    API.update_post(post_type, post_id, {[field]: {values:[{"value":tag}]}}).then(new_post=>{
      $(`#${field}-spinner`).removeClass('active')
      this.addMultiselectItemLayout({name: tag})
      this.hideLayout();
      this.resetInput();
      masonGrid.masonry('layout')
    }).catch(err => { console.error(err) })
  }

  let upgradeUrl = (url)=>{
    if ( !url.includes("http")){
      url = "https://" + url
    }
    if ( !url.startsWith(window.wpApiShare.template_dir)){
      url = url.replace( 'http://', 'https://' )
    }
    return url
  }

  let urlRegex = /[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/gi
  let protocolRegex = /^(?:https?:\/\/)?(?:www.)?/gi
  function resetDetailsFields(){
    window.lodash.forOwn( field_settings, (field_options, field_key)=>{

      if ( field_options.tile === 'details' && !field_options.hidden && post[field_key]){

        if ( field_options.only_for_types && ( field_options.only_for_types === true || field_options.only_for_types.length > 0 && ( post["type"] && !field_options.only_for_types.includes(post["type"].key) ) ) ){
          return
        }
        let field_value = window.lodash.get( post, field_key, false )
        let values_html = ``
        if ( field_options.type === 'text' ){
          values_html = window.lodash.escape( field_value )
        } else if ( field_options.type === 'textarea' ){
          values_html = window.lodash.escape( field_value )
        } else if ( field_options.type === 'date' ){
          values_html = window.lodash.escape( window.SHAREDFUNCTIONS.formatDate( field_value.timestamp ) )
        } else if ( field_options.type === 'key_select' ){
          values_html = window.lodash.escape( field_value.label )
        } else if ( field_options.type === 'multi_select' || field_options.type === 'tags' ){
          values_html = field_value.map(v=>{
            return `${window.lodash.escape( window.lodash.get( field_options, `default[${v}].label`, v ))}`;
          }).join(', ')
        } else if ( ['location', 'location_meta' ].includes(field_options.type) ){
          values_html = field_value.map(v=>{
            return window.lodash.escape(v.matched_search || v.label);
          }).join(' / ')
        } else if ( field_options.type === 'communication_channel' ){
          field_value.forEach((v, index)=>{
            if ( index > 0 ){
              values_html += ', '
            }
            let value = window.lodash.escape(v.value)
            if ( field_key === 'contact_phone' ){
              values_html += `<a dir="auto" class="phone-link" href="tel:${value}" title="${value}">${value}</a>`
            } else if (field_key === "contact_email") {
              values_html += `<a dir="auto" href="mailto:${value}" title="${value}">${value}</a>`
            } else {
              let validURL = new RegExp(urlRegex).exec(value)
              let prefix = new RegExp(protocolRegex).exec(value)
              if (validURL && prefix) {
                let urlToDisplay = ""
                if (field_options.hide_domain && field_options.hide_domain===true) {
                  urlToDisplay = validURL[1] || value
                } else {
                  urlToDisplay = value.replace(prefix[0], "")
                }
                value = upgradeUrl(value)
                value = `<a href="${window.lodash.escape(value)}" target="_blank" >${window.lodash.escape(urlToDisplay)}</a>`
              }
              values_html += value
            }
          })
          let labels = field_value.map(v=>{
            return window.lodash.escape(v.value);
          }).join(', ')
          $(`#collapsed-detail-${field_key} .collapsed-items`).html(`<span title="${labels}">${values_html}</span>`)

        } else if ( ['connection'].includes(field_options.type) ){
          values_html = field_value.map(v=>{
            return window.lodash.escape(v.label);
          }).join(' / ')
        } else {
          values_html = window.lodash.escape( field_value )
        }
        $(`#collapsed-detail-${field_key}`).toggle(values_html !== ``)
        if (field_options.type !== 'communication_channel') {
          $(`#collapsed-detail-${field_key} .collapsed-items`).html(`<span title="${values_html}">${values_html}</span>`)
        }
        if ( field_options.type === "text" && new RegExp(urlRegex).exec(values_html) ){
          window.SHAREDFUNCTIONS.make_links_clickable(`#collapsed-detail-${field_key} .collapsed-items span`)
        }
      }

    })
    phoneLinkClick();
    $( document ).trigger( "dt_record_details_reset", [post] );
  }
  resetDetailsFields();

  function phoneLinkClick() {
    $('.phone-link').on('click', function(event){
      event.preventDefault();
      let phoneNumber = this.href.substring(4).replaceAll(/\s/g, "");
      if ($(`.phone-open-with-container.__${phoneNumber.replace(/^((\+)|(00))/,"")}`).length && $(this).next(`.phone-open-with-container.__${phoneNumber.replace(/^((\+)|(00))/,"")}`)) {
          $(`.phone-open-with-container.__${phoneNumber.replace(/^((\+)|(00))/,"")}`).remove();
      } else {
        $('.phone-open-with-container').remove();
        let PhoneLink = this;
        let messagingServices = window.post_type_fields.contact_phone.messagingServices;
        let messagingServicesLinks = ``;

        for (const service in messagingServices) {
          let link = messagingServices[service].link.replace('PHONE_NUMBER_NO_PLUS', phoneNumber.replace(/^((\+)|(00))/,"")).replace('PHONE_NUMBER', phoneNumber);

          messagingServicesLinks = messagingServicesLinks + `<li><a href="${link}" title="${window.lodash.escape(window.detailsSettings.translations.Open_with)} ${messagingServices[service].name}" target="_blank" class="phone-open-with-link"><img src="${messagingServices[service].icon}"/>${messagingServices[service].name}</a></li>`
        }

        let openWithDiv = `<div class="phone-open-with-container __${phoneNumber.replace(/^((\+)|(00))/,"")}">
        <strong>${window.lodash.escape(window.detailsSettings.translations.Open_with)}...</strong>
          <ul>
            <li><a href="${PhoneLink}" title="${window.lodash.escape(window.detailsSettings.translations.Open_with)} ${window.post_type_fields.contact_phone.name}" target="_blank" class="phone-open-with-link"><img src="${window.lodash.escape( window.wpApiShare.template_dir )}/dt-assets/images/phone.svg"/>${window.post_type_fields.contact_phone.name}</a></li>
            ${(navigator.platform === "MacIntel" || navigator.platform == "iPhone" || navigator.platform == "iPad" || navigator.platform == "iPod") ? `<li><a href="iMessage://${phoneNumber}" title="${window.lodash.escape(window.detailsSettings.translations.Open_with)} iMessage" target="_blank" class="phone-open-with-link"><img src="${window.lodash.escape( window.wpApiShare.template_dir )}/dt-assets/images/imessage.svg"/> iMessage</a></li>` : ""
            }
            ${messagingServicesLinks}
          </ul>
        </div>`

        this.insertAdjacentHTML("afterend", openWithDiv);

        $('.phone-open-with-link').on('click', function() {
          $(this).parents('.phone-open-with-container').remove();
        })
      }
    });
  }

  $('#delete-record').on('click', function(){
    $(this).attr("disabled", true).addClass("loading");
    API.delete_post( post_type, post_id ).then(()=>{
      window.location = window.wpApiShare.site_url + '/' + post_type
    })
  })
  $('#archive-record').on('click', function(){
    $(this).attr("disabled", true).addClass("loading");
    API.update_post( post_type, post_id, {overall_status:"closed"} ).then(()=>{
      $(this).attr("disabled", false).removeClass("loading");
      $('#archive-record-modal').foundation('close');
      $('.archived-notification').show()
    })
  })
  $('#unarchive-record').on('click', function(){
    $(this).attr("disabled", true).addClass("loading");
    API.update_post( post_type, post_id, {overall_status:"active"} ).then(()=>{
      $(this).attr("disabled", false).removeClass("loading");
      $('.archived-notification').hide()
    })
  })

  //autofocus the first input when a modal is opened.
  $(".reveal").on("open.zf.reveal", function () {
    const firstField = $(this).find("input").filter(
      ":not([disabled],[hidden],[opacity=0]):visible:first"
    );
    if (firstField.length !== 0) {
      firstField.focus();
    }
  });

  if (records_list.length > 0) {
    $.each(records_list, function(record_id, post_id_array) {
      if (post_id === post_id_array.ID) {
        current_record = record_id;
        next_record    = record_id+1;
      }
    });

    if ( current_record === 0 || typeof(records_list[current_record-1]) === 'undefined') {
      $(document).find('.navigation-previous').hide();
    } else {
      let link = window.wpApiShare.site_url + '/' + window.detailsSettings.post_type + '/' + records_list[current_record-1].ID
      $(document).find('.navigation-previous').attr('href', link);
      $(document).find('.navigation-previous').removeAttr('style');
    }

    if (typeof (records_list[next_record]) !== 'undefined') {
      let link = window.wpApiShare.site_url + '/' + window.detailsSettings.post_type + '/' + records_list[next_record].ID
      $(document).find('.navigation-next').attr('href', link);
      $(document).find('.navigation-next').removeAttr('style');
    } else {
      $(document).find('.navigation-next').hide();
    }

  } else {
    $(document).find('.navigation-next').removeAttr('style').attr('style', 'display: none;');
  }

  //leave at the end of this file
  masonGrid.masonry({
    itemSelector: '.grid-item',
    percentPosition: true
  });
  //leave at the end of this file
})


// change update needed notification and switch if needed.
function record_updated(updateNeeded) {
  $('.update-needed-notification').toggle(updateNeeded)
  $('.update-needed').prop("checked", updateNeeded)
}

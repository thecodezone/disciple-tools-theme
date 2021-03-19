"use strict"
jQuery(document).ready(function($){

  let post_id = window.detailsSettings.post_id
  let post_type = window.detailsSettings.post_type
  let post = window.detailsSettings.post_fields
  let field_settings = window.detailsSettings.post_settings.fields

  /**
   * Assigned_to
   */
  let assigned_to_input = $(`.js-typeahead-assigned_to`)
  $.typeahead({
    input: '.js-typeahead-assigned_to',
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
        API.update_post('peoplegroups', post_id, {assigned_to: 'user-' + item.ID}).then(function (response) {
          window.lodash.set(post, "assigned_to", response.assigned_to)
          assigned_to_input.val(post.assigned_to.display)
          assigned_to_input.blur()
        }).catch(err => { console.error(err) })
      },
      onResult: function (node, query, result, resultCount) {
        let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
        $('#assigned_to-result-container').html(text);
      },
      onHideLayout: function () {
        $('.assigned_to-result-container').html("");
      },
      onReady: function () {
        if (window.lodash.get(post,  "assigned_to.display")){
          $('.js-typeahead-assigned_to').val(post.assigned_to.display)
        }
      }
    },
  });
  $('.search_assigned_to').on('click', function () {
    assigned_to_input.val("")
    assigned_to_input.trigger('input.typeahead')
    assigned_to_input.focus()
  })

})

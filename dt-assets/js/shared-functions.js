/* global wpApiShare:false */
_ = _ || window.lodash; // make sure lodash is defined so plugins like gutenberg don't break it.

jQuery(document).ready(function ($) {
  // Adds an active state to the top bar navigation
  let ref = "";
  if (wpApiShare && wpApiShare.site_url) {
    ref = window.location.href.replace(wpApiShare.site_url + "/", "");
  } else {
    ref = window.location.pathname;
  }
  let page = `${ref.replace(wpApiShare.site_url, "").split("/")[0] + ""}`;
  $(`div.top-bar-left ul.menu [href^="${wpApiShare.site_url + "/" + page}"]`)
    .parent()
    .addClass("active");

  let collapsed_tiles = window.SHAREDFUNCTIONS.get_json_cookie(
    "collapsed_tiles"
  );
  // expand and collapse tiles, only when a section chevron icon is clicked for that given tile.
  $(".section-header .section-chevron").on("click", function () {
    let tile = $(this).closest(".bordered-box");
    tile.toggleClass("collapsed");
    let tile_id = tile.attr("id");
    if (tile_id && tile_id.includes("-tile")) {
      if (collapsed_tiles.includes(tile_id)) {
        collapsed_tiles = window.lodash.pull(collapsed_tiles, tile_id);
      } else {
        collapsed_tiles.push(tile_id);
      }
      window.SHAREDFUNCTIONS.save_json_cookie(
        "collapsed_tiles",
        collapsed_tiles,
        wpApiShare.post_type
      );
    }
    $(".grid").masonry("layout");
  });
  $(".bordered-box").each((index, item) => {
    let id = $(item).attr("id");
    if (id && id.includes("-tile") && collapsed_tiles.includes(id)) {
      $(item).addClass("collapsed");
    }
  });
});

/**
 *
 * @param type: GET POST DELETE
 * @param url: users/get_users
 * @param data
 * @param base, when using a custom D.T endpoint that does not start with dt/v1
 * @returns {jQuery}
 */
function makeRequest(type, url, data, base = "dt/v1/") {
  //make sure base has a trailing slash if url does not start with one
  if ( !base.endsWith('/') && !url.startsWith('/')){
    base += '/'
  }
  const options = {
    type: type,
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: url.startsWith("http") ? url : `${wpApiShare.root}${base}${url}`,
    beforeSend: (xhr) => {
      xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
    },
  };

  if (data) {
    options.data = type === "GET" ? data : JSON.stringify(data);
  }

  return jQuery.ajax(options);
}

function makeRequestOnPosts(type, url, data) {
  const options = {
    type: type,
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: url.startsWith("http") ? url : `${wpApiShare.root}dt-posts/v2/${url}`,
    beforeSend: (xhr) => {
      xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
    },
  };
  if (data && !window.lodash.isEmpty(data)) {
    options.data = type === "GET" ? data : JSON.stringify(data);
  }
  return jQuery.ajax(options);
}

window.API = {
  get_post: (post_type, postId) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}`),

  create_post: (post_type, fields) =>
    makeRequestOnPosts("POST", `${post_type}`, fields),

  update_post: (post_type, postId, postData) =>
    makeRequestOnPosts("POST", `${post_type}/${postId}`, postData),

  delete_post: (post_type, postId) =>
    makeRequestOnPosts("DELETE", `${post_type}/${postId}`),

  post_comment: (post_type, postId, comment, comment_type = "comment") =>
    makeRequestOnPosts("POST", `${post_type}/${postId}/comments`, {
      comment,
      comment_type,
    }),

  delete_comment: (post_type, postId, comment_ID) =>
    makeRequestOnPosts(
      "DELETE",
      `${post_type}/${postId}/comments/${comment_ID}`
    ),

  update_comment: (
    post_type,
    postId,
    comment_ID,
    comment_content,
    commentType = "comment"
  ) =>
    makeRequestOnPosts(
      "POST",
      `${post_type}/${postId}/comments/${comment_ID}`,
      { comment: comment_content, comment_type: commentType }
    ),

  get_comments: (post_type, postId) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}/comments`),

  toggle_comment_reaction: (postType, postId, commentId, userId, reaction) => {
    makeRequestOnPosts(
      "POST",
      `${postType}/${postId}/comments/${commentId}/react`,
      { user_id: userId, reaction: reaction }
    )
  },

  get_activity: (post_type, postId) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}/activity`),

  get_single_activity: (post_type, postId, activityId) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}/activity/${activityId}`),

  get_shared: (post_type, postId) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}/shares`),

  add_shared: (post_type, postId, userId) =>
    makeRequestOnPosts("POST", `${post_type}/${postId}/shares`, {
      user_id: userId,
    }),

  remove_shared: (post_type, postId, userId) =>
    makeRequestOnPosts("DELETE", `${post_type}/${postId}/shares`, {
      user_id: userId,
    }),

  save_field_api: (post_type, postId, postData) =>
    makeRequestOnPosts("POST", `${post_type}/${postId}`, postData),

  revert_activity: (post_type, postId, activityId) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}/revert/${activityId}`),

  search_users: (query) => makeRequest("GET", `users/get_users?s=${query}`),

  get_filters: () => makeRequest("GET", "users/get_filters"),

  save_filters: (post_type, filter) =>
    makeRequest("POST", "users/save_filters", { filter, post_type }),

  delete_filter: (post_type, id) =>
    makeRequest("DELETE", "users/save_filters", { id, post_type }),

  get_duplicates_on_post: (post_type, postId, args) =>
    makeRequestOnPosts("GET", `${post_type}/${postId}/all_duplicates`, args),

  create_user: (user) => makeRequest("POST", "users/create", user),

  transfer_contact: (contactId, siteId) =>
    makeRequestOnPosts("POST", "contacts/transfer", {
      contact_id: contactId,
      site_post_id: siteId,
    }),

  transfer_contact_summary_update: (contactId, update) =>
    makeRequestOnPosts("POST", "contacts/transfer/summary/send-update", {
      contact_id: contactId,
      update: update,
    }),

  request_record_access: (post_type, postId, userId) =>
    makeRequestOnPosts("POST", `${post_type}/${postId}/request_record_access`, {
      user_id: userId,
    }),

  advanced_search: (search_query, post_type, offset, filters) => makeRequest("GET", `advanced_search`, {
    query: search_query,
    post_type: post_type,
    offset: offset,
    post: filters['post'],
    comment: filters['comment'],
    meta: filters['meta'],
  }, 'dt-posts/v2/posts/search/')
};

function handleAjaxError(err) {
  if (
    window.lodash.get(err, "statusText") !== "abortPromise" &&
    err.responseText
  ) {
    console.trace("error");
    console.log(err);
    // jQuery("#errors").append(err.responseText)
  }
}

jQuery(document)
  .ajaxComplete((event, xhr, settings) => {
    if ( xhr && xhr.responseJSON && settings.type === "POST" ) {
      // Event that a contact record has been updated
      if ( xhr.responseJSON.ID && xhr.responseJSON.post_type ) {
        let request = settings.data ? JSON.parse(settings.data) : {};
        $(document).trigger("dt_record_updated", [xhr.responseJSON, request]);
      }
    }

    if (window.lodash.get(xhr, "responseJSON.data.status") === 401) {
      window.location.reload();
    }
  })
  .ajaxError((event, xhr) => {
    handleAjaxError(xhr);
  });

jQuery(document).on("click", ".help-button", function () {
  jQuery("#help-modal").foundation("open");
  let section = jQuery(this).data("section");
  jQuery(".help-section").hide();
  jQuery(`#${section}`).show();
});
jQuery(document).on("click", ".help-button-tile", function () {
  jQuery("#help-modal-field").foundation("open");
  let section = jQuery(this).data("tile");
  jQuery(".help-section").hide();
  let tile = window.wpApiShare.tiles[section];
  if (tile && window.post_type_fields) {
    if (tile.label) {
      $("#help-modal-field-title").html(window.lodash.escape(tile.label));
    }
    if (tile.description) {
      $("#help-modal-field-description").html(window.lodash.escape(tile.description));
      window.SHAREDFUNCTIONS.make_links_clickable('#help-modal-field-description' )
    } else {
      $("#help-modal-field-description").empty()
    }
    let order = window.wpApiShare.tiles[section]["order"] || [];
    window.lodash.forOwn(window.post_type_fields, (field, field_key) => {
      if ( field.tile === section && !order.includes(field_key)){
        order.push(field_key);
      }
    })
    let html = ``;
    order.forEach( field_key=>{
      let field = window.post_type_fields[field_key]
      if (
        field && field.tile === section && !field.hidden &&
        (field.description || window.lodash.isObject(field.default))
      ) {
        let field_name = `<h2>${window.lodash.escape(field.name)}</h2>`;
        if ( window.wpApiShare.can_manage_dt ){
          let edit_link = `${window.wpApiShare.site_url}/wp-admin/admin.php?page=dt_options&tab=custom-fields&post_type=${window.wpApiShare.post_type}&field-select=${window.wpApiShare.post_type}_${field_key}`
          field_name = `<h2>${window.lodash.escape(field.name)} <span style="font-size: 10px"><a href="${window.lodash.escape(edit_link)}" target="_blank">${window.wpApiShare.translations.edit}</a></span></h2>`;
        }
        html += field_name
        html += `<p>${window.lodash.escape(field.description)}</p>`;

        if (window.lodash.isObject(field.default)) {
          let list_html = ``;
          let first_field_option = true;
          window.lodash.forOwn(field.default, (field_options, field_key) => {
            if( field_options.hasOwnProperty('icon') ) {
              if ( first_field_option ) {
                list_html += `<ul class="help-modal-icon">`;
                first_field_option = false;
              }
              list_html += `<li><img src="${window.lodash.escape(field_options.icon)}">`;
            } else {
              if ( first_field_option ) {
                list_html += `<ul>`;
                first_field_option = false;
              }
              list_html += `<li>`;
            }
            list_html += `<strong>${window.lodash.escape(
              field_options.label
            )}</strong> ${window.lodash.escape(
              !field_options.description ? "" : "- " + field_options.description
            )}</li>`;
          });
          list_html += `</ul>`;
          html += list_html;
        }
      }
    });
    $("#help-modal-field-body").html(html);
  }
  jQuery(`#${section}`).show();
});
jQuery(document).on("click", ".help-button-field", function () {
  jQuery("#help-modal-field").foundation("open");
  let section = jQuery(this).data("section").replace("-help-text", "");
  jQuery(".help-section").hide();

  if (window.post_type_fields && window.post_type_fields[section]) {
    let field = window.post_type_fields[section];
    $("#help-modal-field-title").html(window.lodash.escape(field.name));
    if (field.description) {
      $("#help-modal-field-description").html(window.lodash.escape(field.description));
      window.SHAREDFUNCTIONS.make_links_clickable('#help-modal-field-description' )
    } else {
      $("#help-modal-field-description").empty()
    }
    if (window.lodash.isObject(field.default)) {
      let html = `<ul>`;
      window.lodash.forOwn(field.default, (field_options, field_key) => {
        html += `<li><strong>${window.lodash.escape(
          field_options.label
        )}</strong> ${window.lodash.escape(
          !field_options.description ? "" : "- " + field_options.description
        )}</li>`;
      });
      html += `</ul>`;
      $("#help-modal-field-body").html(html);
    }
  }
  jQuery(`#${section}`).show();
});

window.TYPEAHEADS = {
  typeaheadSource: function (field, url) {
    return {
      contacts: {
        display: ["name", "ID"],
        template: "<span>{{name}}</span>",
        ajax: {
          url: wpApiShare.root + url,
          data: {
            s: "{{query}}",
          },
          beforeSend: function (xhr) {
            xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
          },
          callback: {
            done: function (data) {
              if (typeof typeaheadTotals !== "undefined") {
                typeaheadTotals.field = data.total;
              }
              return data.posts;
            },
          },
        },
      },
    };
  },
  typeaheadUserSource : function (field, url) {
    return {
      users: {
        display: ["name", "user"],
        ajax: {
          url: wpApiShare.root + "dt/v1/users/get_users",
          data: {
            s: "{{query}}",
          },
          beforeSend: function (xhr) {
            xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
          },
          callback: {
            done: function (data) {
              return data.posts || data;
            },
          },
        },
      },
    };
  },
  typeaheadContactsSource: function () {
    return {
      contacts: {
        display: ["name", "ID"],
        ajax: {
          url: wpApiShare.root + "dt-posts/v2/contacts/compact",
          data: {
            s: "{{query}}",
          },
          beforeSend: function (xhr) {
            xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
          },
          callback: {
            done: function (data) {
              return data.posts;
            },
          },
        },
      },
    };
  },
  typeaheadPostsSource: function (post_type, args = {}) {
    return {
      contacts: {
        display: [ "name", "ID", "label" ],
        ajax: {
          url: wpApiShare.root + `dt-posts/v2/${post_type}/compact`,
          data: Object.assign({ s: "{{query}}" }, args),
          beforeSend: function (xhr) {
            xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
          },
          callback: {
            done: function (data) {
              return data.posts;
            },
          },
        },
      },
    };
  },
  typeaheadHelpText: function (resultCount, query, result) {
    let text = "";
    if (result.length > 0 && query) {
      text = wpApiShare.translations.showing_x_items_matching
        .replace(
          "%1$s",
          `<strong>${window.lodash.escape(result.length)}</strong>`
        )
        .replace("%2$s", `<strong>${window.lodash.escape(query)}</strong>`);
    } else if (result.length > 0) {
      text = wpApiShare.translations.showing_x_items.replace(
        "%s",
        `<strong>${window.lodash.escape(result.length)}</strong>`
      );
    } else {
      text = wpApiShare.translations.no_records_found.replace(
        '"{{query}}"',
        `<strong>${window.lodash.escape(query)}</strong>`
      );
    }
    return text;
  },
  contactListRowTemplate: function (query, item) {
    let img = item.user
      ? `<img class="dt-blue-icon" src="${wpApiShare.template_dir}/dt-assets/images/profile.svg?v=2">`
      : "";
    let statusStyle = item.status === "closed" ? 'style="color:gray"' : "";
      return `<span dir="auto" ${statusStyle}>
        <span class="typeahead-user-row" style="width:20px">${img}</span>
        ${window.lodash.escape((item.label ? item.label : item.name))}
        <span dir="auto">(#${window.lodash.escape(item.ID)})</span>
    </span>`;
  },
  share(post_type, id) {
    return $.typeahead({
      input: ".js-typeahead-share",
      minLength: 0,
      maxItem: 0,
      accent: true,
      searchOnFocus: true,
      template: function (query, item) {
        return `<div class="" dir="auto">
          <div>
              <span class="avatar"><img style="vertical-align: text-bottom" src="${window.lodash.escape( item.avatar )}"/></span>
              {{name}} (#${window.lodash.escape( item.ID )})
          </div>
        </div>`
      },
      source: this.typeaheadUserSource(),
      emptyTemplate: window.lodash.escape(window.wpApiShare.translations.no_records_found),
      display: "name",
      templateValue: "{{name}}",
      dynamic: true,
      multiselect: {
        matchOn: ["ID"],
        data: function () {
          var deferred = $.Deferred();
          return window.API.get_shared(post_type, id).then((sharedResult) => {
            return deferred.resolve(
              sharedResult.map((g) => {
                return { ID: g.user_id, name: g.display_name, avatar: g.avatar };
              })
            );
          });
        },
        callback: {
          onCancel: function (node, item) {
            $("#share-result-container").html("");
            window.API.remove_shared(post_type, id, item.ID).catch((err) => {
              Typeahead[".js-typeahead-share"].addMultiselectItemLayout({
                ID: item.ID,
                name: item.name,
                avatar: item.avatar
              });
              $("#share-result-container").html(
                window.lodash.get(err, "responseJSON.message")
              );
            });
          },
        },
      },
      callback: {
        onClick: function (node, a, item, event) {
          window.API.add_shared(post_type, id, item.ID);
        },
        onResult: function (node, query, result, resultCount) {
          if (query) {
            let text = window.TYPEAHEADS.typeaheadHelpText(
              resultCount,
              query,
              result
            );
            $("#share-result-container").html(text);
          }
        },
        onHideLayout: function () {
          $("#share-result-container").html("");
        },
      },
    });
  },
  defaultContactTypeahead: function () {
    return {
      minLength: 0,
      accent: true,
      searchOnFocus: true,
      maxItem: 20,
      template: this.contactListRowTemplate,
      source: this.typeaheadContactsSource(),
      display: "name",
      templateValue: "{{name}}",
      dynamic: true,
    };
  },
};

window.SHAREDFUNCTIONS = {
  getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(";");
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == " ") {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  },
  get_json_cookie(cname, default_val = []) {
    let cookie = this.getCookie(cname);
    try {
      default_val = JSON.parse(cookie);
    } catch (e) {}
    return default_val;
  },
  save_json_cookie(cname, json, path = "") {
    if (path) {
      path = window.location.pathname.split(path)[0] + path;
      path = path.replace(/^\/?([^\/]+(?:\/[^\/]+)*)\/?$/, "/$1"); // add leading and remove trailing slashes
    }
    document.cookie = `${cname}=${JSON.stringify(json)};path=${path}`;
  },
  createCustomFilter(field, value) {
    return ({
      fields: [
        {
          [field]: value,
        }
      ]
    })
  },
  create_url_for_list_query(postType, query, labels) {
    const encodedQuery = window.SHAREDFUNCTIONS.encodeJSON(query);
    const encodedLabels = window.SHAREDFUNCTIONS.encodeJSON(labels);
    return window.wpApiShare.site_url + `/${postType}?query=${encodedQuery}&labels=${encodedLabels}`;
  },
  encodeJSON(json) {
    return window.btoa(JSON.stringify(json))
  },
  decodeJSON(encodedJSON) {
    try {
      return JSON.parse(window.atob(encodedJSON))
    } catch (error) {
      return null
    }
  },
  get_langcode() {
    let langcode = document.querySelector("html").getAttribute("lang")
      ? document.querySelector("html").getAttribute("lang").replace("_", "-")
      : "en"; // get the language attribute from the HTML or default to english if it doesn't exists.
      return langcode;
  },
  get_days_of_the_week_initials(format = 'narrow'){
    let langcode = window.SHAREDFUNCTIONS.get_langcode();
    let now = new Date()
    const int_format = new Intl.DateTimeFormat(langcode, {weekday:format}).format;
    return [...Array(7).keys()].map((day) => int_format(new Date().getTime() - (now.getDay() - day) * 86400000));
  },
  get_months_labels(format = 'long'){
    let langcode = window.SHAREDFUNCTIONS.get_langcode();
    const int_format = new Intl.DateTimeFormat(langcode, {month:format}).format;
    return [...Array(12).keys()].map((month) => int_format(new Date( Date.UTC(2021, month, 1))));
  },
  formatDate(date, with_time = false, short_month = false) {
    let langcode = window.SHAREDFUNCTIONS.get_langcode();
    if (langcode === "fa-IR") {
      //This is a check so that we use the gergorian (Western) calendar if the users locale is Farsi. This is the calendar used primarily by Farsi speakers outside of Iran, and is easily understood by those inside.
      langcode = `${langcode}-u-ca-gregory`;
    }
    const options = { year: "numeric", month: "long", day: "numeric" };
    if (with_time) {
      options.hour = "numeric";
      options.minute = "numeric";
    } else {
      options.timeZone = "UTC";
    }
    if (short_month) {
      options.month = "short"
    }

    if ( isNaN(date) ){
      return false
    }
    const formattedDate = new Intl.DateTimeFormat(langcode, options).format(
      date * 1000
    );

    return formattedDate;
  },
  /*
  * Allow links and @ mentions to be displayed in comments section
  */
  formatComment(comment) {
    if(comment){
      let mentionRegex = /\@\[(.*?)\]\((.+?)\)/g
      comment = comment.replace(mentionRegex, (match, text, id)=>{
        /* dir=auto means that @ will be put to the left of the name if the
          * mentioned name is LTR, and to the right if the mentioned name is
          * RTL, instead of letting the surrounding dir determine the placement
          * of @ */
        return `<a dir="auto">@${text}</a>`
      })
      let urlRegex = /((href=('|"))|(\[|\()?|(http(s)?:((\/)|(\\))*.))*(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//\\=]*)/g
      comment = comment.replace(urlRegex, (match)=>{
        let url = match
        if(match.indexOf("@") === -1 && match.indexOf("[") === -1 && match.indexOf("(") === -1 && match.indexOf("href") === -1) {
          if (match.indexOf("http") === 0 && match.indexOf("www.") === -1) {
            url = match
          }
          else if (match.indexOf("http") === -1 && match.indexOf("www.") === 0) {
            url = "http://" + match
          }
          else if (match.indexOf("www.") === -1) {
            url = "http://www." + match
          }
          return `<a href="${url}" rel="noopener noreferrer" target="_blank">${match}</a>`
        }
        return match
      })
      let linkRegex = /\[(.*?)\]\((.+?)\)/g; //format [text](link)
      comment = comment.replace(linkRegex, (match, text, url)=>{
        if (text.includes("http") && !url.includes("http")){
          [url, text] = [text, url]
        }
        url = url.includes('http') ? url : `${window.wpApiShare.site_url}/${window.wpApiShare.post_type}/${url}`
        return `<a href="${url}">${text}</a>`
      })

    }
    return comment
  },
  convertArabicToEnglishNumbers(string) {
    return string
      .replace(/[\u0660-\u0669]/g, function (c) {
        return c.charCodeAt(0) - 0x0660;
      })
      .replace(/[\u06f0-\u06f9]/g, function (c) {
        return c.charCodeAt(0) - 0x06f0;
      });
  },
  get_url_param(name) {
    let results = new RegExp("[?&]" + name + "=([^&#]*)").exec(
      window.location.search
    );
    return results !== null ? results[1] || 0 : false;
  },
  /**
   * Lodash escape all string values in a simple key, value object.
   *
   * @param obj Must be a simple map of key, value pairs. E.g. a translation mapping.
   */
  escapeObject(obj) {
    return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
        return [ key, window.lodash.escape(value)]
    }))
  },
  make_links_clickable( selector ){
    //make text links clickable in a section
    let elem_text = $(selector).html()
    let urlRegex = /((href=('|"))|(\[|\()?|(http(s)?:((\/)|(\\))*.))*(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,8}\b([-a-zA-Z0-9@:%_\+.~#?&//\\=]*)/g
    elem_text = elem_text.replace(urlRegex, (match)=>{
      let url = match
      if(match.indexOf("@") === -1 && match.indexOf("[") === -1 && match.indexOf("(") === -1 && match.indexOf("href") === -1) {
        if (match.indexOf("http") === 0 && match.indexOf("www.") === -1) {
          url = match
        }
        else if (match.indexOf("http") === -1 && match.indexOf("www.") === 0) {
          url = "http://" + match
        }
        else if (match.indexOf("www.") === -1) {
          url = "http://www." + match
        }
        return `<a href="${url}" rel="noopener noreferrer" target="_blank">${match}</a>`
      }
      return match
    })
    $(selector).html(elem_text)
  },
};

let date_ranges = {
  "All time": [moment(0), moment().endOf("year")],
  [moment().format("MMMM YYYY")]: [
    moment().startOf("month"),
    moment().endOf("month"),
  ],
  [moment().subtract(1, "month").format("MMMM YYYY")]: [
    moment().subtract(1, "month").startOf("month"),
    moment().subtract(1, "month").endOf("month"),
  ],
  [moment().format("YYYY")]: [
    moment().startOf("year"),
    moment().endOf("year"),
  ],
  [moment().subtract(1, "year").format("YYYY")]: [
    moment().subtract(1, "year").startOf("year"),
    moment().subtract(1, "year").endOf("year"),
  ],
  [moment().subtract(2, "year").format("YYYY")]: [
    moment().subtract(2, "year").startOf("year"),
    moment().subtract(2, "year").endOf("year"),
  ],
};

window.METRICS = {
  setupDatePicker: function (endpoint_url, callback, startDate, endDate) {
    $(".date_range_picker").daterangepicker(
      {
        showDropdowns: true,
        ranges: date_ranges,
        linkedCalendars: false,
        locale: {
          format: "YYYY-MM-DD",
        },
        startDate: startDate || moment(0),
        endDate: endDate || moment().endOf("year").format("YYYY-MM-DD"),
      },
      function (start, end, label) {
        $(".loading-spinner").addClass("active");
        jQuery
          .ajax({
            type: "GET",
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            url: `${endpoint_url}?start=${start.format(
              "YYYY-MM-DD"
            )}&end=${end.format("YYYY-MM-DD")}`,
            beforeSend: function (xhr) {
              xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
            },
          })
          .done(function (data) {
            $(".loading-spinner").removeClass("active");
            if (label === "Custom Range") {
              label =
                start.format("MMMM D, YYYY") +
                " - " +
                end.format("MMMM D, YYYY");
            }
            callback(data, label, start, end);
          })
          .fail(function (err) {
            console.log("error");
            console.log(err);
            // jQuery("#errors").append(err.responseText)
          });
        // console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
      }
    );
  },
  setupDatePickerWithoutEndpoint: function (callback, startDate, endDate) {
    $(".date_range_picker").daterangepicker(
      {
        showDropdowns: true,
        ranges: date_ranges,
        linkedCalendars: false,
        locale: {
          format: "YYYY-MM-DD",
        },
        startDate: startDate || moment(0),
        endDate: endDate || moment().endOf("year").format("YYYY-MM-DD"),
      },
      function (start, end, label) {
        callback(start, end, label);
      }
    );
  }
};

/**
 * use the class .copy_to_clipboard to copy the contents of data-value="" to the clipboard.
 */
jQuery(document).ready(function(){
  jQuery('.copy_to_clipboard').on('click', function(){
    let str = jQuery(this).data('value')
    const el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    const selected =
      document.getSelection().rangeCount > 0
        ? document.getSelection().getRangeAt(0)
        : false;
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    if (selected) {
      document.getSelection().removeAllRanges();
      document.getSelection().addRange(selected);
    }
    alert('Copied')
  })
})

/**
 * Magic Link Support
 */
window.sha256 = (ascii) => {
  function rightRotate(value, amount) {
    return (value>>>amount) | (value<<(32 - amount));
  }

  var mathPow = Math.pow;
  var maxWord = mathPow(2, 32);
  var lengthProperty = 'length'
  var i, j; // Used as a counter across the whole file
  var result = ''

  var words = [];
  var asciiBitLength = ascii[lengthProperty]*8;

  //* caching results is optional - remove/add slash from front of this line to toggle
  // Initial hash value: first 32 bits of the fractional parts of the square roots of the first 8 primes
  // (we actually calculate the first 64, but extra values are just ignored)
  var hash = sha256.h = sha256.h || [];
  // Round constants: first 32 bits of the fractional parts of the cube roots of the first 64 primes
  var k = sha256.k = sha256.k || [];
  var primeCounter = k[lengthProperty];
  /*/
  var hash = [], k = [];
  var primeCounter = 0;
  //*/

  var isComposite = {};
  for (var candidate = 2; primeCounter < 64; candidate++) {
    if (!isComposite[candidate]) {
      for (i = 0; i < 313; i += candidate) {
        isComposite[i] = candidate;
      }
      hash[primeCounter] = (mathPow(candidate, .5)*maxWord)|0;
      k[primeCounter++] = (mathPow(candidate, 1/3)*maxWord)|0;
    }
  }

  ascii += '\x80' // Append Ƈ' bit (plus zero padding)
  while (ascii[lengthProperty]%64 - 56) ascii += '\x00' // More zero padding
  for (i = 0; i < ascii[lengthProperty]; i++) {
    j = ascii.charCodeAt(i);
    if (j>>8) return; // ASCII check: only accept characters in range 0-255
    words[i>>2] |= j << ((3 - i)%4)*8;
  }
  words[words[lengthProperty]] = ((asciiBitLength/maxWord)|0);
  words[words[lengthProperty]] = (asciiBitLength)

  // process each chunk
  for (j = 0; j < words[lengthProperty];) {
    var w = words.slice(j, j += 16); // The message is expanded into 64 words as part of the iteration
    var oldHash = hash;
    // This is now the undefinedworking hash", often labelled as variables a...g
    // (we have to truncate as well, otherwise extra entries at the end accumulate
    hash = hash.slice(0, 8);

    for (i = 0; i < 64; i++) {
      var i2 = i + j;
      // Expand the message into 64 words
      // Used below if
      var w15 = w[i - 15], w2 = w[i - 2];

      // Iterate
      var a = hash[0], e = hash[4];
      var temp1 = hash[7]
        + (rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25)) // S1
        + ((e&hash[5])^((~e)&hash[6])) // ch
        + k[i]
        // Expand the message schedule if needed
        + (w[i] = (i < 16) ? w[i] : (
            w[i - 16]
            + (rightRotate(w15, 7) ^ rightRotate(w15, 18) ^ (w15>>>3)) // s0
            + w[i - 7]
            + (rightRotate(w2, 17) ^ rightRotate(w2, 19) ^ (w2>>>10)) // s1
          )|0
        );
      // This is only used once, so *could* be moved below, but it only saves 4 bytes and makes things unreadble
      var temp2 = (rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22)) // S0
        + ((a&hash[1])^(a&hash[2])^(hash[1]&hash[2])); // maj

      hash = [(temp1 + temp2)|0].concat(hash); // We don't bother trimming off the extra ones, they're harmless as long as we're truncating when we do the slice()
      hash[4] = (hash[4] + temp1)|0;
    }

    for (i = 0; i < 8; i++) {
      hash[i] = (hash[i] + oldHash[i])|0;
    }
  }

  for (i = 0; i < 8; i++) {
    for (j = 3; j + 1; j--) {
      var b = (hash[i]>>(j*8))&255;
      result += ((b < 16) ? 0 : '') + b.toString(16);
    }
  }
  return result;
};
window.make_magic_link = ( site, root, type, hash, action ) => {
  let link = site + '/' + root + '/' + type + '/' + hash + '/'
  if ( action ) {
    link = link + action
  }
  return link
}

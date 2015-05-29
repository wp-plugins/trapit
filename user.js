window.TRAPIT = window.TRAPIT || {};
(function () {
    var queue_items_div = "trapit-body-masonry";
    var iso;

    function trap_name_click_handler(trap_id, th) {
        var active_traps = th.parentNode.querySelectorAll(".trapit-category-trap.active");
        Array.prototype.forEach.call(active_traps, function (active_trap) {
            active_trap.classList.remove("active");
        });
        th.classList.add("active");

        // Old URL format
        //$url = "https://{$opt_vals[trapit_hostname]}.trap.it/api/v4/{$opt_vals[trapit_slug]}/traps/{$trap_id}/queue/?pretty=true&type=bundle&size=500";
        var url = "https://" + trapit_opt_vals["trapit_hostname"] + "/api/v4/" + trapit_opt_vals["trapit_slug"] + "/traps/" + trap_id + "/queue/?pretty=true&type=bundle&size=20";

        console.debug("Inside trap_name_click_handler");

        fetch_json_then(url, load_queue_items);
    }

    function all_traps_click_handler() {
        var url = "https://" + trapit_opt_vals["trapit_hostname"] + "/api/v4/" + trapit_opt_vals["trapit_slug"] + "/public-traps/queue/?url=20";
        var all_traps = TRAPIT.ROOT.querySelector(".all-traps");
        var active_headers = all_traps.parentNode.querySelectorAll(".trapit-category-header.active");
        
        console.debug("Inside all_traps_click_handler");
        hide_sidebar_traps();
        Array.prototype.forEach.call(active_headers, function (header) {
            header.classList.remove("active");
        });
        all_traps.classList.add("active");

        fetch_json_then(url, load_queue_items);
    }

    function fetch_json_then(url, action, options) {
        // fetch JSON
        var args = [url];
        if (options) {
            args.push(options);
        }
        
        fetch.apply(null, args).then(
            function (response) {
                switch (response.status) {
                case 200:
                    // Examine the text in the response
                    response.json().then(action(options));
                    break;
                case 204:
                    // no documents in queue
                    console.log("Trap is empty. Status Code: 204");
                    var d = div("message no-content",
                                "This trap has no candidate content. Please check again later for content.");
                    var element = d.getElements();
                    var trapit_body_masonry = TRAPIT.ROOT.querySelector(".trapit-body-masonry");

                    // empty out any current contents of the target div and show no-content message
                    var new_div = trapit_body_masonry.cloneNode(false);
                    new_div.appendChild(element);
                    trapit_body_masonry.parentNode.replaceChild(new_div, trapit_body_masonry);
                    break;
                default:
                    console.log('Looks like there was a problem. Status Code: ' +
                                response.status);
                }
            }
        ).catch(function (err) {
            console.log('Fetch Error: ', err);
        });
    }

    
    function load_queue_items(options) {
    return function (data, append) {
        var next = data.next;
        var prev = data.prev;
        var records = data.records || [];

        if (!TRAPIT.ROOT) {
            console.log("Attempted to load queue items before root element was created.");
            return;
        }

        var div = TRAPIT.ROOT.querySelector("." + queue_items_div);

        if (!append) {
            // empty out any current contents of the target div
            var new_div = div.cloneNode(false);
            new_div.style.display = 'none';
            div.parentNode.replaceChild(new_div, div);
            div = new_div;
        }
        
        var new_items = records.map(function (record, i) {
            var doc = record.document;
            var sizes = doc.images.sizes || [];
            var image_url = null;
            var height;

            sizes.some(function (size, j) {
                if (size.dimensions.lastIndexOf('320x', 0) === 0) {
                    image_url = size.url;
                    height = size.dimensions.substring(4);
                    return true;
                }
            });

            return create_item(record, image_url, height);
        });

        new_items.forEach(function (item) {
            div.appendChild(item);
        });

        if (!append) {
            div.style.display = '';
            
            // init Isotope
            iso = new Isotope(div, {
                // options
                itemSelector: '.trapit-masonry-item',
                layoutMode: 'masonry',
                columnWidth: 324,
                gutter: 40 //,
                //"isOriginLeft": true
            });
        } else {
            iso.appended(new_items);
            //iso.layout();
        }
        
        var app = TRAPIT.ROOT.querySelector(".app");
        var trapit_body_masonry = TRAPIT.ROOT.querySelector(".trapit-body-masonry");
        var wpadminbar = document.getElementById("wpadminbar");

        var loading_prev = false;
        window.onscroll = function (event) {
            if (loading_prev) {
                return;
            }
            var body_height = trapit_body_masonry.offsetHeight; //document.getElementsByTagName("body")[0].offsetHeight;
            // if body_height - pageYOffset <= innerHeight, we're 100% scrolled to bottom
            var total = body_height - innerHeight;
            var bottom = total - pageYOffset;
            if (bottom <= 100) {
                loading_prev = true;
                enable_queue_spinner();
                // infinite scroll here
                //  fetch previous bundle of data
                //   pass to load_queue_items with append set to true
                (options ? fetch(prev, options) : fetch(prev)).then(function (response) {
                    disable_queue_spinner();
                    switch (response.status) {
                    case 200:
                        response.json().then(function (data) {
                            load_queue_items(options)(data, true);
                        });
                        break;
                    case 204:
                        // 204 is sent when the queue is extinguished
                        break;
                    default:
                        console.log('Looks like there was a problem fetching ' + prev + '\n Status Code: ' +
                                    response.status);
                    }
                }).catch(function (err) {
                    disable_queue_spinner();
                    console.log('Fetch Error: ', err);
                });
            }
        };

        jQuery(trapit_body_masonry).resize(function (event) {
            console.log(event);
            app.style.minHeight = wpadminbar.clientHeight + trapit_body_masonry.clientHeight + "px";
        });

        app.style.minHeight = wpadminbar.clientHeight + trapit_body_masonry.clientHeight + "px";
    };
    }


    var p = Ele("p");
    var div = Ele("div");
    var span = Ele("span");
    var article = Ele("article");
    var figure = Ele("figure");
    var img = Ele("img");
    var italic = Ele("i");
    var a = Ele("a");
    var section = Ele("section");

    var input = Ele("input");
    var form = Ele("form");
    var button = Ele("button");

    function create_item(record, image_url, image_height) {
        var doc = record.document;
        var source = record.source.name;
        var summary = doc.summary;
        var title = doc.title;
        var primary_name = record.trap.name.toUpperCase();
        if (primary_name === "AGGREGATE TRAP") {
            primary_name = record.origin.name.toUpperCase();
        }
        var image = image_url ? img("article-img", null, {src: image_url, width: "320", height: image_height}) : null;

        var post_form = form("post-form", [input(null, null, {type: "hidden",
                                                              value: doc.title,
                                                              name: "trapit_title"}),
                                           input(null, null, {type: "hidden",
                                                              value: doc.summary,
                                                              name: "trapit_summary"}),
                                           input(null, null, {type: "hidden",
                                                              value: doc.original,
                                                              name: "trapit_original"}),
                                           input(null, null, {type: "hidden",
                                                              value: image_url,
                                                              name: "trapit_image_url"}),
                                           input(null, null, {type: "hidden",
                                                              value: doc.id,
                                                              name: "trapit_id"}),
                                           input(null, null, {type: "hidden",
                                                              value: "Y",
                                                              name: "trapit_submit_hidden"})
                                          ],
                             {"method": "post", "action": ""});

        var submit_action = {onclick: function () {
            var form = this.parentNode.querySelector("form");
            form.submit();
        }};

        var published = new Date(1000 * doc.published);
        published = format_published(published);
        var icon_share_data_uri = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUiIGhlaWdodD0iMTUiIHZpZXdCb3g9IjAgMCAxNSAxNSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48dGl0bGU+c2hhcmU8L3RpdGxlPjxnIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIyIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik03LjUgMkgydjExaDExVjcuNU03IDhsNi02TTkgMWg1djUiLz48L2c+PC9zdmc+';
        var icon_share_btn = button("btn btn-main", "POST", {title: "POST", type: "button"});
        var clearfix = div("clearfix");
        var item = div("trapit-masonry-item masonry item enduser landing is-read-only animate-items-enter animate-items-enter-active", [//div("comment-box"), // much stuff under comment box
            article(null,
                    [figure(null, [div("notification", span("message")),
                                   a("", [div("preload-bg"),
                                          image],
                                     {href: doc.original})
                                  ]),
                     section(null, [a("primary-name", primary_name),
                                    div("title is-read-only", [p("", a("section-title-rel", span("section-title-span", title), {href: doc.original}))] //, skippping some markup / clearfix stuff
                                       ),
                                    div("source-info", source),
                                    div("summary is-read-only", p("summary-p", a("summary-rel", span("summary-span", summary),
                                                                                 {href: doc.original})) // summary edit actions here?
                                       )]),
                     div("info date-info", span("publish-date", published)),
                     clearfix,
                     div("action", [icon_share_btn, clearfix], submit_action),
                     post_form,
                     clearfix])]);
        var item_elements = item.getElements();
        return item_elements;
    }

    function Ele(eletype) {
        return function (description, contents, opts) {
            return {
                description: description,
                contents: contents,
                opts: opts,
                getElements: function () {
                    var element = document.createElement(eletype);
                    var contents = this.contents;

                    if (this.description) {
                        element.className = this.description;
                    }

                    if (!Array.isArray(contents)) {
                        contents = contents ? [contents] : [];
                    }

                    contents.forEach(function (content) {
                        if (content) {
                            if (typeof content === "string") {
                                element.appendChild(document.createTextNode(content));
                            } else {
                                // assume a single Ele object
                                element.appendChild(content.getElements());
                            }
                        }
                    });

                    for (var key in (this.opts || {})) {
                        element[key] = this.opts[key];
                    }

                    return element;
                }
            };
        };
    }

    function format_published(date) {
        var date_string = date.toLocaleString("en-US", {month: "long",
                                                        year: "numeric",
                                                        day: "numeric",
                                                        hour: "numeric",
                                                        minute: "numeric"});
        var i = date_string.lastIndexOf(',');
        return date_string.substr(0, i) + " -" + date_string.substr(i + 1).toLowerCase();
    }

    function get_delay_promise(delay) {
        delay = delay || 5000;
        return function (result) {
            return new Promise(function (resolve, reject) {
                setTimeout(function () {
                    resolve(result);
                }, delay);
            });
        };
    }
    
    function enable_queue_spinner() {
        var trapit_body = TRAPIT.ROOT.querySelector(".trapit-body-masonry");
        var spinner = document.createElement("div");
        spinner.className = "trapit-queue-spinner";
        trapit_body.appendChild(spinner);
    }

    function disable_queue_spinner() {
        var spinners = TRAPIT.ROOT.querySelectorAll(".trapit-queue-spinner");
        Array.prototype.forEach.call(spinners, function (spinner) {
            spinner.parentNode.removeChild(spinner);
        });
    }
    
    function load_template() {
        var trapit_window = document.getElementById("trapit-window");
        //var list_container = document.getElementById("trapit-list-container");
        var root = trapit_window.createShadowRoot();
        var template = document.getElementById("trapit-template");
        var clone = document.importNode(template.content, true);
        root.appendChild(clone);

        //root.appendChild(list_container);
        //var content_traps = document.querySelector("trapit-content-traps");
        //root.appendChild(content_traps);

        //var content = document.createElement("content");
        //root.appendChild(content);
        TRAPIT.ROOT = root;
    }

    function hide_sidebar_traps() {
        var all_category_traps = TRAPIT.ROOT.querySelectorAll(".trapit-category-trap");
        var i = all_category_traps.length;
        while (i--) {
            all_category_traps[i].style.display = "none";
        }
    }
    
    function trap_category_click_handler(th, category_id) {
        var $ = jQuery;
        var $this = $(th);
        var $traps = $this.nextUntil(".trapit-category-header");
        var active_headers = th.parentNode.querySelectorAll(".trapit-category-header.active");
        var this_is_active = th.classList.contains("active");
        var all_traps_div = TRAPIT.ROOT.querySelector(".all-traps");

        console.debug("Inside trap_category_click_handler");

        hide_sidebar_traps();

        all_traps_div.classList.remove("active");
        Array.prototype.forEach.call(active_headers, function (header) {
            header.classList.remove("active");
        });

        // toggle activity
        if (!this_is_active) {
            $traps.show();
            th.classList.add("active");

            load_alligator_view(category_id);
        }
    }

    function get_fetch_options(options) {
        var authorization = trapit_opt_vals["trapit_user_id"] + ":" + trapit_opt_vals["trapit_api_key"];

        authorization = "Basic " + btoa(authorization);
        var default_options = {
            method: 'GET',
            //mode: "cors-with-forced-preflight",
            headers: {
                Accept: 'application/json',
                Authorization: authorization
            }
        };
        if (options) {
            for (var key in options) {
                default_options[key] = options[key];
            }
        }
        return default_options;
    }
    
    function load_alligator_view(category_id) {
        // show the alligator view for the category

        // sample curl query:
        // curl -u "{trapit_opt_vals["trapit_user_id"]}:<session>" -L https://{trapit_opt_vals["trapit_hostname"]}/api/v3/{trapit_opt_vals["trapit_slug"]}/aggregates/team_id={team-id}&category_id={category_id}&public=true&content_type='text'&visible=false/ -X GET
        
        var url = ["https://", trapit_opt_vals["trapit_hostname"],
                   "/api/v3/", trapit_opt_vals["trapit_slug"], "/aggregates/",
                   (category_id !== "0" ? "category_id=" + category_id + "&": ""),
                   "public=true",
                   "&visible=true",
                   // endpoint does not support random such as: "&random=", Math.random(),
                   "/"].join("");
        
        // fetch the URL, get response, fetch queue items with response info
        var options = get_fetch_options();
        fetch(url, options).then(
            function (response) {
                switch (response.status) {
                case 202:
                    // documentation says to look for a 202 response
                    "https://test8.trap.it/api/v3/t8/aggregates/category_id=9f079202fd3f41debe295bb591c49311&public=true&visible=true/"
                case 201:
                    // actually receive a 201 response upon first call

                    response.json().then(fetch_json_then(url, load_queue_items, options));
                    break;
                case 200:
                    // alligator had been previously created
                    // handle response by loading the returned items
                    response.json().then(load_queue_items(options));
                    break;
                case 204:
                    // no documents available
                    console.log("Trap is empty. Status Code: 204");
                    var d = div("message no-content",
                                "This trap has no candidate content. Please check again later for content.");
                    var element = d.getElements();
                    var trapit_body_masonry = TRAPIT.ROOT.querySelector(".trapit-body-masonry");

                    // empty out any current contents of the target div and show no-content message
                    var new_div = trapit_body_masonry.cloneNode(false);
                    new_div.appendChild(element);
                    trapit_body_masonry.parentNode.replaceChild(new_div, trapit_body_masonry);
                    break;
                default:
                    console.log('Looks like there was a problem. Status Code: ' +
                                response.status);
                }
            }).catch(function (err) {
                console.log('Fetch Error: ', err);
            });
    }

    var oldWpOnload = (typeof wpOnload === 'function') ? wpOnload : null;
    wpOnload = function () {
        if (oldWpOnload) {
            oldWpOnload();
        }

        load_template();
        hide_sidebar_traps();

        var wpfooter = document.getElementById("wpfooter");
        //wpfooter.style.display = "none";
        wpfooter.style.visibility = "hidden";
        var wpbody_content = document.getElementById("wpbody-content");
        wpbody_content.style.paddingBottom = "0px";
        var app = TRAPIT.ROOT.querySelector(".app");
        app.style.minHeight = window.innerHeight + "px";

        // make All traps the default view
        var all_traps = app.querySelector(".all-traps");
        all_traps_click_handler();
        all_traps.classList.add("active");
    };

    TRAPIT.trap_name_click_handler = trap_name_click_handler;
    TRAPIT.all_traps_click_handler = all_traps_click_handler;
    TRAPIT.trap_category_click_handler = trap_category_click_handler;
    TRAPIT.load_template = load_template;
})();

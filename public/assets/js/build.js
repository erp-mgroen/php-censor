var Build = Class.extend({
    buildId: null,
    plugins: {},
    observers: {},
    buildData: {},
    queries: {},
    updateInterval: null,

    init: function(build) {
        var self = this;
        self.buildId = build;
    },

    setupBuild: function (buildData, linkTemplate) {
        var self = this;
        self.buildData = buildData;
        self.fileLinkTemplate = linkTemplate;

        self.registerQuery('build-updated', 5);

        $(window).on('build-updated', function(data) {

            self.buildData = data.queryData;

            // If the build has finished, stop updating every 10 seconds:
            if (self.buildData && self.buildData.status > 1) {
                self.cancelQuery('build-updated');
                $(window).trigger({type: 'build-complete'});
            }

            if (self.buildData) {
                $('.build-duration').html(self.buildData.duration ? (self.buildData.duration + ' ' + Lang.get('seconds')) : ('0 ' + Lang.get('seconds')));
                $('.build-started').html(self.buildData.started ? self.buildData.started : '');
                $('.build-finished').html(self.buildData.finished ? self.buildData.finished : '');
                $('#log pre').html(self.buildData.log);
                $('.errors-table tbody').html(self.buildData.error_html);

                if (self.buildData.errors == 0) {
                    $('.errors-label').hide();
                } else {
                    $('.errors-label').text(self.buildData.errors);
                    $('.errors-label').show();
                }

                switch (self.buildData.status) {
                    case 0:
                        $('body').removeClass('skin-red skin-green skin-yellow');
                        $('body').addClass('skin-blue');
                        break;

                    case 1:
                        $('body').removeClass('skin-red skin-green skin-blue');
                        $('body').addClass('skin-yellow');
                        break;

                    case 2:
                        $('body').removeClass('skin-red skin-blue skin-yellow');
                        $('body').addClass('skin-green');
                        break;

                    case 3:
                        $('body').removeClass('skin-blue skin-green skin-yellow');
                        $('body').addClass('skin-red');
                        break;

                }
            }
        });
    },

    registerQuery: function(name, seconds, query) {
        var self = this;
        var uri = 'build/ajax-meta/' + self.buildId;
        var query = query || {};

        var cb = function() {
            var fullUri = window.APP_URL + uri;

            if (name == 'build-updated') {
                fullUri = window.APP_URL + 'build/ajax-data/' + self.buildId;
            }

            $.ajax({
                dataType: "json",
                url: fullUri,
                data: query,
                success: function(data) {
                    $(window).trigger({type: name, queryData: data});
                },
                error: handleFailedAjax
            });
        };

        if (seconds != -1) {
            self.queries[name] = setInterval(cb, seconds * 1000);
        }

        return cb;
    },

    cancelQuery: function (name) {
        clearInterval(this.queries[name]);
    },

    registerPlugin: function(plugin) {
        this.plugins[plugin.id] = plugin;
        plugin.register();
    },

    storePluginOrder: function () {
        var renderOrder = [];

        $('.ui-plugin > div').each(function() {
            renderOrder.push($(this).attr('id'));
        });

        localStorage.setItem('app-plugin-order', JSON.stringify(renderOrder));
    },

    renderPlugins: function() {
        var self = this;
        var rendered = [];
        var renderOrder = localStorage.getItem('app-plugin-order');

        if (renderOrder) {
            renderOrder = JSON.parse(renderOrder);
        } else {
            renderOrder = ['build-lines-chart', 'build-warnings-chart'];
        }

        for (var idx in renderOrder) {
            var key = renderOrder[idx];

            // Plugins have changed, clear the order.
            if (typeof self.plugins[key] == 'undefined') {
                localStorage.setItem('app-plugin-order', []);
            }

            self.renderPlugin(self.plugins[key]);
            rendered.push(key);
        }

        for (var key in this.plugins) {
            if (rendered.indexOf(key) == -1) {
                self.renderPlugin(self.plugins[key]);
            }
        }

        $(window).trigger({type: 'build-updated', queryData: self.buildData});
    },

    renderPlugin: function(plugin) {
        var output = plugin.render();

        output = $('<div class="box-body"></div>').append(output);

        var container = $('<div></div>').addClass('ui-plugin ' + plugin.css).attr('id', plugin.id);
        var content = $('<div></div>').append(output);
        content.addClass('box');

        if (plugin.title) {
            content.prepend(
                '<div class="box-header"><h3 class="box-title">'+plugin.title+'</h3>'+
                '<div class="box-tools pull-right">'+
                '<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">'+
                '<i class="fa fa-minus"></i></button></div></div>'
            );
        }

        container.append(content);

        $('#plugins').append(container);
    },

    UiPlugin: Class.extend({
        id: null,
        css: 'col-xs-12',
        box: false,

        init: function(){
        },

        register: function() {
            var self = this;

            $(window).on('build-updated', function(data) {
                self.onUpdate(data);
            });
        },

        render: function () {
            return '';
        },

        onUpdate: function (build) {

        }
    })
});

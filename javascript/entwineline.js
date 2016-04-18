
window.Microblog = window.Microblog || {}

;(function($) {
	
	marked.setOptions({
		sanitize: true
	});
	
	var postContainer = $('<div>');
	

	Microblog.Timeline = function () {
				
		var mentionify = function (textinput) {
			textinput.autogrow();
			
			textinput.textcomplete([
				{ // html
					// mentions: ['yuku_t'],
					match: /\B@(\w*)$/,
					search: function (term, callback) {
						if (term && term.length > 2) {
							SSWebServices.get('microBlog', 'findMember', {searchTerm: term}, function (data) {
								if (data && data.response) {
									var items = [];
									for (var i in data.response.items) {
										var member = data.response.items[i];
										items.push('@' + member.Title + ':' + member.ID);
									}
									callback(items);
								}
							});
						} else {
							callback([]);
							return false;
						}
					},
					
					index: 1,
					replace: function (mention) {
						return '' + mention + ' ';
					}
				}
			], {zIndex: '201'}).overlay([
				{
					match: /\B@(.*?):\d+/g,
					css: {
						'background-color': '#d8dfea'
					}
				}
			]);
//			
//			textinput.mentionsInput({
//				onDataRequest:function (mode, query, callback) {
//				  var data = [
//					{ id:1, name:'Kenneth Auchenberg', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
//					{ id:2, name:'Jon Froda', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
//					{ id:3, name:'Anders Pollas', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
//					{ id:4, name:'Kasper Hulthin', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
//					{ id:5, name:'Andreas Haugstrup', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
//					{ id:6, name:'Pete Lacey', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' }
//				  ];
//
//				  data = _.filter(data, function(item) { return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1 });
//
//				  callback.call(this, data);
//				}
//			  });
		}

		return {
			mentionify: mentionify
		}
	}();
	
	$(function () {
		$('.timeline-box').entwine({
			currentOffset: null,
			maxId: 0, 
			lastId: 0,
			refreshTimer: null,
			refreshTime: 20000,
			pendingUpdate: false,
			pendingLoad: false,
			loading: false,
			feed: null,

			onmatch: function () {
				this.refreshTime(25000);
				this.setFeed($(this).find('.StatusFeed'));
			},
			setFeed: function (f) {
				if (!f) {
					return;
				}
				this.feed(f);

				if (this.feed().hasClass('autorefresh') && !this.refreshTimer()) {
					var self = this;
					this.refreshTimer(setTimeout(function () {
						self.refreshTimeline(0, true);
					}, self.refreshTime()));
				}
			},
			calcMaxMinPosts: function () {

				if (!this.currentOffset()) {
					this.currentOffset($(this).find('input[name=postOffset]').val());
				}

				var me = this;
				$(this).find('div.microPost').each(function (index) {
					var postId = parseInt($(this).attr('data-id'));
					if (postId > me.maxId()) {
						me.maxId(postId);
					}
					if ($(this).hasClass('toplevel')) {
						me.lastId(postId);
					}
				})
			}, 
			refreshTimeline: function (since, reschedule) {
				if (this.pendingUpdate()) {
					return this.pendingUpdate();
				}

				this.calcMaxMinPosts();

				if (!since) {
					since = this.maxId();
				}

				this.loading(true);

				this.pendingUpdate(this.getPosts({since: since, replies: 1}));

				if (!this.pendingUpdate()) {
					return;
				}

				var self = this;
				this.pendingUpdate().done(function () {
					self.pendingUpdate(false);
					self.loading(false);
					// self.feed may be null now if the timeline object has been removed from the DOM
					// by this point
					if (self.feed() && self.feed().hasClass('autorefresh')) {
						if (reschedule) {
							setTimeout(function () {
								self.refreshTimeline(false, reschedule);
							}, self.refreshTime());
						}
					}
				})
				return this.pendingUpdate();
			},
			morePosts: function () {
				this.calcMaxMinPosts();

				if (this.pendingLoad()) {
					return this.pendingLoad();
				}

				if (this.lastId() > 0) {
					// see what we're sorting by
					var sortBy = $(this).find('input[name=timelineSort]').val();
					var params = {};
					if (sortBy) {
						params.sort = sortBy;
					}
					if (this.currentOffset()) {
						params.offset = this.currentOffset();
					}
					var restrict = {before: this.lastId()};
					var self = this;
					this.pendingLoad(this.getPosts(params, true).done(function () {
						self.pendingLoad(false);
					}));
					return this.pendingLoad();
				}
			},
			getPosts: function (params, append, callback) {
				var url = $(this).find('input[name=timelineUpdateUrl]').val();
				if (!url) {
					return;
				}
				var self = this;
				return $.get(url, params, function (data) {
					postContainer.empty();
					if (data && data.length > 0) {
						postContainer.append(data);

						self.currentOffset(postContainer.find('input[name=postOffset]').val());

						postContainer.find('div.microPost').each (function () {
							var me = $(this);
							// first see if we're already inside another post's replies. If so, we don't move it
							if (me.parent().hasClass('postReplies')) {
								return;
							}

	//						var wrapper = $('<div class="newposts">');
							var parentId = parseInt(me.attr('data-parent'));
							if (!parentId) {
								if (append) {
									me.appendTo(self.feed());
								} else {
									me.prependTo(self.feed());
								}
							} else {
								var target = $('#post' + parentId);
								if (target.length) {
									var targetReplies = target.find('.postReplies:first');
									me.prependTo(targetReplies);
								}
							}
	//						wrapper.append(me);
							me.effect("highlight", {}, 3000);
							// done here because we've just removed and re-added to the dom?
	//						wrapper.find('textarea.expandable').autogrow();
						});
					}
				});
			},
			deletePost: function (id) {
				if (!id) {
					return;
				}
				var params = {
					'postType': 'MicroPost',
					'postID': id
				};

				SSWebServices.post('microBlog', 'deletePost', params, function (data) {
					if (data && data.response) {
						// marked as deleted, versus completely removed
						if (data.response.Deleted == 0) {
							$('#post' + id).fadeOut('slow');
						} else {
							$('#post' + id).find('div.postText').html(data.response.Content);
						}
					}

				})
			},
			vote: function (id, dir) {
				var params = {
					'postType': 'MicroPost',
					'postID': id,
					'dir': dir
				};

				return SSWebServices.post('microBlog', 'vote', params, function (data) {
					if (data && data.response) {
						$('span.ownerVotes').each(function () {
							if ($(this).attr('data-id') == Microblog.Member.MemberID) {
								$(this).text(data.response.RemainingVotes).effect("highlight", {}, 2000);
							}
						})
					}
				})
			},
			editPost: function (id) {
				return SSWebServices.get('microBlog', 'rawPost', {id: id}, function (post) {
					if (post && post.response) {
						$('.postEditorField').remove();
						var editorField = $('<textarea name="Content" class="postContent expandable postEditorField">');

						var postId = 'post' + id;
						var postContent = $($('#' + postId).find('.postText')[0]);
						postContent.append(editorField);
						editorField.val(post.response.OriginalContent ? post.response.OriginalContent : post.response.Content);

						Microblog.Timeline.mentionify(editorField);

						var save = $('<input type="button" value="Save" class="postEditorField">');
						save.insertAfter(editorField);

						save.click(function () {
							var data = {
								'Content'	: editorField.val()
							};

							var params = {
								postID: id,
								postType: 'MicroPost',
								data: data
							}

							editorField.remove()
							save.remove()

							SSWebServices.post('microBlog', 'savePost', params).done(function (data) {
								if (data && data.response) {
									if (data.response.RenderedContent) {
										postContent.html(data.response.RenderedContent);
									} else if (typeof(marked) != 'undefined') {
										postContent.html(marked(data.response.Content));
									}
								}
							});
						})
					}
				})
			}
		});
		
		
		$.entwine('microblog', function ($) {
			$('div.postText a').entwine({
				onclick: function () {
					var postId = $(this).parents('.microPost').attr('data-id');
					Microblog.track('timeline', 'post_click', $(this).attr('href'));
					this._super();
				}
			});
			
			$(document).on('click', 'a.post-expander', function (e) {
				e.preventDefault()
				var postId = $(this).attr('data-id');
				if ($(this).data('toggled')) {
					$(this).data('toggled', 0);
					$('#' + postId).addClass('collapsed-post').removeClass('expanded-post');
				} else {
					$(this).data('toggled', 1);
					$('#' + postId).addClass('expanded-post').removeClass('collapsed-post');
				}
				
				return false;
			})

			$('.timeago').entwine({
				onmatch: function () {
					if ($.fn.timeago) {
						this.timeago();
					}
				}
			})
			
			$(document).on('click', 'div.microPost.unread', function () {
				$(this).removeClass('unread');
			})

			$(document).on('click', 'a.deletePost', function (e) {
				var postId = $(this).parents('.microPost').attr('data-id');
				$(this).parents('.timeline-box').deletePost(postId);
//				Microblog.Timeline.deletePost(postId);
				return false;
			})
			
			$(document).on('click', 'a.vote', function (e) {
				e.preventDefault();

				$('a.vote').removeClass('voted');
				$('a.vote').addClass('not-voted');
				
				$(this).removeClass('not-voted').addClass('voted');
				
				var _this = $(this);
				var dir = $(this).attr('data-dir'); 
//				Microblog.Timeline
				$(this).parents('.timeline-box').vote($(this).attr('data-id'), dir).done(function (object) {
					if (object.response) {
						_this.siblings('.upCount').text(object.response.Up);
						_this.siblings('.downCount').text(object.response.Down);
					}
				});
				return false;
			});

			$('div.microPost').entwine({
				onmatch: function () {
					if ($(this).attr('data-owner') == Microblog.Member.MemberID && $(this).attr('data-editable')) {
						var editId = $(this).attr('data-id');
						var button = $('<a href="#" class="editButton">edit post</a>');
						$($(this).find('.postOptions')[0]).append(button);
						button.click(function (e) {
							e.preventDefault();
							button.parents('.timeline-box').editPost(editId)
						})
					}
					
					// and PostTarget checks, depending on our timeline context
					if ($('input[name=PostTarget]').length === 0) {
						// we can show the post-target-links because we're not in the context of 
						// hooking into specific posts
						// Disabled here; specific implementations can add this code themselves to
						// enabled the display of these links if desired
						// $(this).find('.post-target-link').show();
					}
				}
			})

			$('a.moreposts').entwine({
				onclick: function () {
					var _this = this;
					// caution - leak possible!! need to switch to new 'from' stuff in entwine
					var doMore = $(this).parents('.timeline-box').morePosts();
					if (doMore) {
						doMore.done(function () {
							_this.appendTo($(_this).parents('.StatusFeed'));
						});
					}
					return false;
				}
			})
			
			// Auto replace image URLs 
			$('div.microPost a').entwine({
				onmatch: function () {
					var href = this.attr('href');
					if (href && href.length && href.lastIndexOf('.') > 0 && !this.hasClass('force-link')) {
						var ext = href.substr(href.lastIndexOf('.') + 1);
						if ($.inArray(ext, ['png', 'jpg', 'gif']) > -1) {
							// see if this actually has an image already
							if ($(this).find('img').length == 0) {
								var img = $('<img>').attr('src', href);
								this.text('').append(img).attr('target', '_blank');
							}
						}
					}
					this._super();
				}
			})

			$('form.replyForm').entwine({
				onmatch: function () {
					$(this).attr('action', $('#PostFormUrl').val());
					Microblog.Timeline.mentionify($(this).find('textarea'));
					var thisform = this;
					this.ajaxForm(function (data, status, xhr, form) {
						$('form.replyForm').find('textarea').val('').trigger('keydown');
						$(thisform).parents('.timeline-box').refreshTimeline();
						if (data && data.response) {
							$('span.ownerVotes').each(function () {
								if ($(this).attr('data-id') == Microblog.Member.MemberID) {
									$(this).text(data.response.RemainingVotes).effect("highlight", {}, 2000);
								}
							})
						}

						$('input[name=action_savepost]').removeAttr('disabled');
						$('form.replyForm').find('input[name=action_savepost]').attr('value', 'Reply');
					}).fail(function () {
						$('input[name=action_savepost]').removeAttr('disabled');
						$('form.replyForm').find('input[name=action_savepost]').attr('value', 'Reply');
					})
				},
				onsubmit: function () {
					$(this).find('input[name=action_savepost]').attr('disabled', 'disabled');
					$('div.postPreview').hide();
					return true;
				}
			})

			$('form#Form_PostForm').entwine({
				onmatch: function () {
//					$(this).find('textarea.expandable').autogrow();
					Microblog.Timeline.mentionify($(this).find('textarea.expandable'));
					
					var thisform = this;
					$(this).ajaxForm({
						beforeSubmit: function (fields, form) {
						},
						success: function (data) {
							$('#Form_PostForm').find('textarea').removeClass('expanded-content').val('').trigger('keydown');
							$('#Form_PostForm').find('input[type=text]').removeClass('expanded-content').val('');
							$('input[name=action_savepost]').removeAttr('disabled');
							$('#Form_PostForm').find('input[name=action_savepost]').attr('value', 'Add');
							var b = $(thisform).parents('.timeline-box');
							$(thisform).parents('.timeline-box').refreshTimeline();
							if (data && data.response) {
								$('span.ownerVotes').each(function () {
									if ($(this).attr('data-id') == Microblog.Member.MemberID) {
										$(this).text(data.response.RemainingVotes).effect("highlight", {}, 2000);
									}
								})
							}
						}
					}).fail(function () {
						$('input[name=action_savepost]').removeAttr('disabled');
						$('input[name=action_savepost]').attr('value', 'Add');
					})
				},
				onsubmit: function () {
					$(this).find('input[name=action_savepost]').attr('disabled', 'disabled');
					$('div.postPreview').hide();
					return true;
				}
			});

			$('a.replyToPost, button.replyToPost').entwine({
				onclick: function (e) {
					e.preventDefault();
					var postId = $(this).closest('div.microPost').attr('data-id');
					var replyForm = $('#replyTo' + postId);
					if (replyForm.length == 0) {
						replyForm = $(this).parents('.postReplies').siblings('form.replyForm');
					}
					replyForm.show().removeClass('visually-hidden').find('textarea').focus();
				}
			});
			
			$(document).on('click', 'input.specific-users', function () {
				if ($('.post-specific-users').is(':visible')) {
					$('.post-specific-users').slideUp();
				} else {
					$('.post-specific-users').slideDown();
				}
			});
			
			var boundUploads = false;
			
			$(document).on('click', 'input[name=uploadTrigger]', function () {
				var context = $(this).parents('.timeline-box');
				var attachment = context.find('div#Attachment');
				attachment.toggle();
				if (!attachment.data('boundUploads')) {
					attachment.data('boundUploads', true);
					var backend = attachment.find('.dropzone-holder').data('dropzoneInterface').backend;
					backend.on('success', function (file, fileId) {
						var contentField = context.find('#Form_PostForm textarea[name=Content]');
						SSWebServices.get('microBlog', 'fileLookup', {fileId: fileId}, function (file) {
							if (file.response && file.response.ID) {
								file = file.response;
								var txt = '[' + file.Title + '](' + file.Link + ')';
								if (file.IsImage) {
									txt = '![' + file.Title + '](' + file.Link + ')';
								} else if (file.Link.endsWith('.mp4')) {
									txt = '[mb_video]' + file.Link + '[/mb_video]';
								}
								var current = contentField.val();
								if (!current) {
									current = '';
								}
								if (current.length > 0) {
									current += "\n";
								}
								current += txt;
								contentField.val(current);

								contentField.trigger('keyup');
							}
						});
						return false;
					})
				}
			})
			
			if (typeof(marked) != 'undefined') {
				$('textarea.postContent.preview').entwine({
					onmatch: function () {
						var parent = $(this).parent(); //('form');
						var preview = $('<div>').addClass('postPreview').hide();
						preview.insertAfter(parent);
						$(this).keyup(function () {
							preview.html(marked($(this).val())).show();
						})
						this._super();
					}
				})
			}
		})
	})
	
})(jQuery);

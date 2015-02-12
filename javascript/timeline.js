
window.Microblog = window.Microblog || {}

;(function($) {
	
	Microblog.Timeline = function () {
		var feed = null;
		
		var refreshTimer = null;
		var refreshTime = 20000;
		var pendingUpdate = false;
		var pendingLoad = false;
		
		var postContainer = $('<div>');
		
		var loading = false;
		
		var maxId = 0, lastId = 0;
		
		var currentOffset = null;
		
		var calcMaxMinPosts = function () {
			
			if (!currentOffset) {
				currentOffset = $('input[name=postOffset]').val();
			}
			
			$('div.microPost').each(function (index) {
				var postId = parseInt($(this).attr('data-id'));
				if (postId > maxId) {
					maxId = postId;
				}
				
				if ($(this).hasClass('toplevel')) {
					lastId = postId;
				}
			})
		}

		var refreshTimeline = function (since, reschedule) {
			if (pendingUpdate) {
				return pendingUpdate;
			}

			calcMaxMinPosts();

			if (!since) {
				since = maxId;
			}

			loading = true;

			pendingUpdate = getPosts({since: since, replies: 1});
			
			if (!pendingUpdate) {
				return;
			}

			pendingUpdate.done(function () {
				pendingUpdate = null;
				loading = false;
				if (feed.hasClass('autorefresh')) {
					if (reschedule) {
						setTimeout(function () {
							refreshTimeline(false, reschedule);
						}, refreshTime);
					}
				}
			})
			return pendingUpdate;
		}

		var morePosts = function () {
			calcMaxMinPosts();
			
			if (pendingLoad) {
				return pendingLoad;
			}

			if (lastId > 0) {
				// see what we're sorting by
				var sortBy = $('input[name=timelineSort]').val();
				var params = {};
				if (sortBy) {
					params.sort = sortBy;
				}
				if (currentOffset) {
					params.offset = currentOffset;
				}
				var restrict = {before: lastId};
				pendingLoad = getPosts(params, true).done(function () {
					pendingLoad = null;
				});
				return pendingLoad;
			}
		}

		var getPosts = function (params, append, callback) {
			var url = $('input[name=timelineUpdateUrl]').val();
			if (!url) {
				return;
			}
			return $.get(url, params, function (data) {
				postContainer.empty();
				if (data && data.length > 0) {
					postContainer.append(data);
					
					currentOffset = postContainer.find('input[name=postOffset]').val();
					
					postContainer.find('div.microPost').each (function () {
						var me = $(this);
						// first see if we're already inside another post's replies. If so, we don't move it
						if (me.parent().hasClass('postReplies')) {
							return;
						}

						var wrapper = $('<div class="newposts">');
						var parentId = parseInt(me.attr('data-parent'));
						if (!parentId) {
							if (append) {
								wrapper.appendTo(feed);
							} else {
								wrapper.prependTo(feed);
							}
						} else {
							var target = $('#post' + parentId);
							if (target.length) {
								var targetReplies = target.find('.postReplies:first');
								wrapper.prependTo(targetReplies);
							}
						}
						wrapper.append(me);
						wrapper.effect("highlight", {}, 3000);
						// done here because we've just removed and re-added to the dom?
//						wrapper.find('textarea.expandable').autogrow();
					})

					/*
					$('.fileUploadForm').fileupload('disable');
					$('.fileUploadForm').fileupload('enable');
					*/
					if ($.fileupload && $('.fileUploadForm').length) {
						$('.fileUploadForm').fileupload(
							'option',
							'dropZone',
							$('textarea.postContent')
						);
					}
				}
			});
		}

		var deletePost = function (id) {
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
		}

		var vote = function (id, dir) {
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
		};

		var setFeed = function (f) {
			if (!f) {
				return;
			}
			feed = f;
			
			if (feed.hasClass('autorefresh') && !refreshTimer) {
				refreshTimer = setTimeout(function () {
					refreshTimeline(0, true);
				}, refreshTime)
			}
		}
		
		var editPost = function (id) {
			return SSWebServices.get('microBlog', 'rawPost', {id: id}, function (post) {
				if (post && post.response) {
					$('.postEditorField').remove();
					var editorField = $('<textarea name="Content" class="postContent expandable postEditorField">');
					editorField.val(post.response.OriginalContent ? post.response.OriginalContent : post.response.Content);
					editorField.autogrow();
					
					var postId = 'post' + id;
					var postContent = $($('#' + postId).find('.postText')[0]);
					postContent.append(editorField);

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
								} else if (typeof(Showdown) != 'undefined') {
									var converter = new Showdown.converter();
									postContent.html(converter.makeHtml(data.response.Content));
									delete converter;
								}
							}
						});
					})
				}
/*					
 					post = post.response;
					post.Content += 'derd ';
					
					*/
			})
		}

		return {
			init: function () {
				calcMaxMinPosts();
			},
			refresh: refreshTimeline,
			more: morePosts,
			deletePost: deletePost,
			vote: vote,
			setFeed: setFeed,
			editPost: editPost
		}
	}();

	$(function () {
		$.entwine('microblog', function ($) {
			Microblog.Timeline.init();
			
			$('div.postText a').entwine({
				onclick: function () {
					var postId = $(this).parents('.microPost').attr('data-id');
					Microblog.track('timeline', 'post_click', $(this).attr('href'));
					this._super();
				}
			})
			
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

			$('div#StatusFeed').entwine({
				onmatch: function () {
					Microblog.Timeline.setFeed(this);
				}
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
				Microblog.Timeline.deletePost(postId);
				return false;
			})
			
			$(document).on('click', 'a.vote', function (e) {
				e.preventDefault();
				var _this = $(this);
				var dir = $(this).attr('data-dir'); 
				Microblog.Timeline.vote($(this).attr('data-id'), dir).done(function (object) {
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
							Microblog.Timeline.editPost(editId)
						})
					}
				}
			})

			$('a.moreposts').entwine({
				onclick: function () {
					var _this = this;
					// caution - leak possible!! need to switch to new 'from' stuff in entwine
					var doMore = Microblog.Timeline.more();
					if (doMore) {
						doMore.done(function () {
							_this.appendTo('#StatusFeed')
						});
					}
					return false;
				}
			})
			
			// Auto replace image URLs 
			$('a').entwine({
				onmatch: function () {
					var href = this.attr('href');
					if (href && href.length && href.lastIndexOf('.') > 0) {
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
					$(this).find('textarea.expandable').autogrow();
					
					this.ajaxForm(function (data) {
						$('form.replyForm').find('textarea').val('');
						Microblog.Timeline.refresh();
						if (data && data.response) {
							$('span.ownerVotes').each(function () {
								if ($(this).attr('data-id') == Microblog.Member.MemberID) {
									$(this).text(data.response.RemainingVotes).effect("highlight", {}, 2000);
								}
							})
						}

						$('form.replyForm').find('input[name=action_savepost]').removeAttr('disabled');
						$('form.replyForm').find('input[name=action_savepost]').attr('value', 'Reply');
					}).fail(function () {
						$('form.replyForm').find('input[name=action_savepost]').removeAttr('disabled');
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
					$(this).find('textarea.expandable').autogrow();
					$(this).ajaxForm(function (data) {
						$('#Form_PostForm').find('textarea').removeClass('expanded-content').val('');
						$('#Form_PostForm').find('input[type=text]').removeClass('expanded-content').val('');
						$('#Form_PostForm').find('input[name=action_savepost]').removeAttr('disabled');
						$('#Form_PostForm').find('input[name=action_savepost]').attr('value', 'Add');
						Microblog.Timeline.refresh();
						if (data && data.response) {
							$('span.ownerVotes').each(function () {
								if ($(this).attr('data-id') == Microblog.Member.MemberID) {
									$(this).text(data.response.RemainingVotes).effect("highlight", {}, 2000);
								}
							})
						}
					});
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
						replyForm = $(this).parent().siblings('form.replyForm');
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
			
			$(document).on('click', 'input[name=uploadTrigger]', function () {
				$('div.uploadForm').show().find(':file').click();
				return false;
			})
			
			if (typeof(Showdown) != 'undefined') {
				var converter = new Showdown.converter();
				$('textarea.postContent.preview').entwine({
					onmatch: function () {
						var parent = $(this).parent(); //('form');
						var preview = $('<div>').addClass('postPreview').hide();
						preview.insertAfter(parent);
						$(this).keyup(function () {
							preview.html(converter.makeHtml($(this).val())).show();
						})
						this._super();
					}
				})
			}
			

			// TODO Fix issue where dynamically entered textarea.postContent isn't bound as a drop source
			$('.fileUploadForm').entwine({
				onmatch: function () {
					var uploadList = $('#uploadedFiles');
					var uploadParent = 0;
					$(this).fileupload({
						dataType: 'json',
						dropZone: $('textarea.postContent'),
						formData: function(form) {
							var formData = [
								{name: 'SecurityID', value: $('input[name=SecurityID]').val()}
								// {name: 'ID', value: $(form).find(':input[name=ID]').val()}
							];
							if (uploadParent > 0) {
								formData.push({name: 'ParentID', value: uploadParent})
							}
							
							formData.push({name: 'LoggedInUsers', value: $('input[name=LoggedInUsers]').val()});
							formData.push({name: 'Members', value: $('select#Form_PostForm_Members').val()});
							formData.push({name: 'Groups', value: $('select#Form_PostForm_Groups').val()});
							
							return formData;
						},
						drop: function (e, data) {
							$('div.uploadForm').show();
							uploadParent = 0;
							if (e.currentTarget) {
								var parent = $(e.currentTarget).closest('div.microPost');
								if (parent.length) {
									// set the uploadParent id
									uploadParent = parent.attr('data-id');
								}
							}
							$.each(data.files, function (index, file) {
								var li = $('<li class="pending">').appendTo(uploadList).text(file.name);
								li.attr('data-name', file.name);
								$('<span>0%</span>').appendTo(li);
								file.listElem = li;
							});
						},
						done: function (e, data) {
							if (data.result && data.files[0] && data.files[0].listElem) {
								if (data.result.ID) {
									data.files[0].listElem.find('span').text('100%');
								} else if (data.result[0]) {
									data.files[0].listElem.find('span').text(data.result[0].message).css('color', 'red');
								} else {
									data.files[0].listElem.find('span').text('Err').css('color', 'red');
								}
							} 
							
							Microblog.Timeline.refresh();
						},
						
						send: function(e, data) {
							if (data.dataType && data.dataType.substr(0, 6) === 'iframe') {
								// Iframe Transport does not support progress events.
								// In lack of an indeterminate progress bar, we set
								// the progress to 100%, showing the full animated bar:
								data.total = 1;
								data.loaded = 1;
								$(this).data('fileupload').options.progress(e, data);
							}
						},
						progress: function(e, data) {
							// if (data.context) {
								var value = parseInt(data.loaded / data.total * 100, 10) + '%';
								if (data.files[0] && data.files[0].listElem) {
									data.files[0].listElem.find('span').text(value);
								}
								// data.contextElem.find('span')
								// data.context.find('.ss-uploadfield-item-status').html((data.total == 1)?ss.i18n._t('UploadField.LOADING'):value);
								// data.context.find('.ss-uploadfield-item-progressbarvalue').css('width', value);
							// }
						}
						/*errorMessages: {
							// errorMessages for all error codes suggested from the plugin author, some will be overwritten by the config comming from php
							1: ss.i18n._t('UploadField.PHP_MAXFILESIZE'),
							2: ss.i18n._t('UploadField.HTML_MAXFILESIZE'),
							3: ss.i18n._t('UploadField.ONLYPARTIALUPLOADED'),
							4: ss.i18n._t('UploadField.NOFILEUPLOADED'),
							5: ss.i18n._t('UploadField.NOTMPFOLDER'),
							6: ss.i18n._t('UploadField.WRITEFAILED'),
							7: ss.i18n._t('UploadField.STOPEDBYEXTENSION'),
							maxFileSize: ss.i18n._t('UploadField.TOOLARGESHORT'),
							minFileSize: ss.i18n._t('UploadField.TOOSMALL'),
							acceptFileTypes: ss.i18n._t('UploadField.INVALIDEXTENSIONSHORT'),
							maxNumberOfFiles: ss.i18n._t('UploadField.MAXNUMBEROFFILESSHORT'),
							uploadedBytes: ss.i18n._t('UploadField.UPLOADEDBYTES'),
							emptyResult: ss.i18n._t('UploadField.EMPTYRESULT')
						},*/
					});
				}
			})
		})
	})
	
})(jQuery);

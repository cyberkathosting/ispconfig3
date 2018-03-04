module:depends("http");

local jid_split = require "util.jid".prepped_split;
local b64 = require "util.encodings".base64.encode;
local sha1 = require "util.hashes".sha1;
local stanza = require "util.stanza".stanza;
local json = require "util.json".encode_ordered;

local function require_resource(name)
    local icon_path = module:get_option_string("presence_icons", "icons");
    local f, err  = module:load_resource(icon_path.."/"..name);
    if f then
        return f:read("*a");
    end
    module:log("warn", "Failed to open image file %s", icon_path..name);
    return "";
end

local statuses = { online = {}, away = {}, xa = {}, dnd = {}, chat = {}, offline = {} };

local function handle_request(event, path)
  local status, message;
  local jid, type = path:match("([^/]+)/?(.*)$");
  if jid then
    local user, host = jid_split(jid);
    if host and not user then
        user, host = host, event.request.headers.host;
        if host then host = host:gsub(":%d+$", ""); end
    end
    if user and host then
      local user_sessions = hosts[host] and hosts[host].sessions[user];
      if user_sessions then
        status = user_sessions.top_resources[1];
        if status and status.presence then
          message = status.presence:child_with_name("status");
          status = status.presence:child_with_name("show");
          if not status then
            status = "online";
          else
            status = status:get_text();
          end
          if message then
            message = message:get_text();
          end
        end
      end
    end
  end
  status = status or "offline";

  statuses[status].image = function()
    return { status_code = 200, headers = { content_type = "image/png" },
      body =  require_resource("status_"..status..".png")
    };
  end;
  statuses[status].html = function()
    local jid_hash = sha1(jid, true);
    return { status_code = 200, headers = { content_type = "text/html" },
      body =  [[<!DOCTYPE html>]]..
        tostring(
          stanza("html")
            :tag("head")
            :tag("title"):text("XMPP Status Page for "..jid):up():up()
            :tag("body")
            :tag("div", { id = jid_hash.."_status", class = "xmpp_status" })
            :tag("img", { id = jid_hash.."_img", class = "xmpp_status_image xmpp_status_"..status,
              src = "data:image/png;base64,"..b64(require_resource("status_"..status..".png")) }):up()
            :tag("span", { id = jid_hash.."_status_name", class = "xmpp_status_name" })
              :text("\194\160"..status):up()
            :tag("span", { id = jid_hash.."_status_message", class = "xmpp_status_message" })
              :text(message and "\194\160"..message.."" or "")
        )
    };
  end;
  statuses[status].text = function()
    return { status_code = 200, headers = { content_type = "text/plain" },
      body = status
    };
  end;
  statuses[status].message = function()
    return { status_code = 200, headers = { content_type = "text/plain" },
      body = (message and message or "")
    };
  end;
  statuses[status].json = function()
    return { status_code = 200, headers = { content_type = "application/json" },
      body = json({
        jid    = jid,
        show   = status,
        status = (message and message or "null")
      })
    };
  end;
  statuses[status].xml = function()
    return { status_code = 200, headers = { content_type = "application/xml" },
      body = [[<?xml version="1.0" encoding="utf-8"?>]]..
        tostring(
          stanza("result")
            :tag("jid"):text(jid):up()
            :tag("show"):text(status):up()
            :tag("status"):text(message)
        )
      };
  end

  if ((type == "") or (not statuses[status][type])) then
    type = "image"
  end;

  return statuses[status][type]();
end

module:provides("http", {
    default_path = "/status";
    route = {
        ["GET /*"] = handle_request;
    };
});

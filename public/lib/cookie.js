// 创建cookie
function setCookie (name, value, expires) {
  var date = new Date()
  // 设置过期时间
  date.setTime(date.getTime() + expires)
  document.cookie = name + '=' + escape(value) + ((expires == null) ? '' : ';expires=' + date.toUTCString())
}

// 获取cookie
function getCookie (name) {
  var cookies = window.document.cookie
  if (cookies.length > 0) {
    var startIdx = window.document.cookie.indexOf(name + '=')
    var endIdx = 0
    if (startIdx !== -1) {
      startIdx = startIdx + name.length + 1
      endIdx = cookies.indexOf(';', startIdx)
      if (endIdx === -1) {
        endIdx = cookies.length
      }
      return unescape(cookies.substring(startIdx, endIdx))
    }
  }
  return ''
}
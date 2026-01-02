/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "../modules/interactions/assets/js/interactions-utils.js":
/*!***************************************************************!*\
  !*** ../modules/interactions/assets/js/interactions-utils.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";


var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ "../node_modules/@babel/runtime/helpers/interopRequireDefault.js");
Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.config = void 0;
exports.getKeyframes = getKeyframes;
exports.parseAnimationName = parseAnimationName;
var _slicedToArray2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "../node_modules/@babel/runtime/helpers/slicedToArray.js"));
var _window$ElementorInte;
var config = exports.config = ((_window$ElementorInte = window.ElementorInteractionsConfig) === null || _window$ElementorInte === void 0 ? void 0 : _window$ElementorInte.constants) || {
  defaultDuration: 300,
  defaultDelay: 0,
  slideDistance: 100,
  scaleStart: 0,
  easing: 'linear'
};
function calculateSlideDistance(element, direction) {
  if (!element) {
    return config.slideDistance;
  }
  var rect = element.getBoundingClientRect();
  var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
  var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
  var isLtr = 'ltr' === document.documentElement.dir || 'ltr' === document.body.dir;
  switch (direction) {
    case 'left':
      return Math.min((isLtr ? rect.left : rect.right) + rect.width, viewportWidth + rect.width);
    case 'right':
      return Math.min(viewportWidth - (isLtr ? rect.right : rect.left) + rect.width, viewportWidth + rect.width);
    case 'top':
      return Math.min(rect.top + rect.height, viewportHeight + rect.height);
    case 'bottom':
      return Math.min(viewportHeight - rect.bottom + rect.height, viewportHeight + rect.height);
    default:
      return config.slideDistance;
  }
}
function getKeyframes(effect, type, direction) {
  var element = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;
  var isIn = 'in' === type;
  var keyframes = {};
  var hasDirection = !!direction;
  if ('fade' === effect) {
    if (hasDirection && isIn) {
      keyframes.opacity = [0, 0, 0.2, 0.6, 1];
    } else if (hasDirection && !isIn) {
      keyframes.opacity = [1, 0.8, 0.4, 0, 0];
    } else {
      keyframes.opacity = isIn ? [0, 1] : [1, 0];
    }
  }
  if ('scale' === effect) {
    keyframes.scale = isIn ? [config.scaleStart, 1] : [1, config.scaleStart];
  }
  if (direction) {
    var distance = calculateSlideDistance(element, direction);
    var movement = {
      left: {
        x: isIn ? [-distance, 0] : [0, -distance]
      },
      right: {
        x: isIn ? [distance, 0] : [0, distance]
      },
      top: {
        y: isIn ? [-distance, 0] : [0, -distance]
      },
      bottom: {
        y: isIn ? [distance, 0] : [0, distance]
      }
    };
    Object.assign(keyframes, movement[direction]);
  }
  return keyframes;
}
function parseAnimationName(name) {
  var _name$split = name.split('-'),
    _name$split2 = (0, _slicedToArray2.default)(_name$split, 6),
    trigger = _name$split2[0],
    effect = _name$split2[1],
    type = _name$split2[2],
    direction = _name$split2[3],
    duration = _name$split2[4],
    delay = _name$split2[5];
  return {
    trigger: trigger,
    effect: effect,
    type: type,
    direction: direction || null,
    duration: duration ? parseInt(duration, 10) : config.defaultDuration,
    delay: delay ? parseInt(delay, 10) : config.defaultDelay
  };
}

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/arrayLikeToArray.js":
/*!******************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/arrayLikeToArray.js ***!
  \******************************************************************/
/***/ ((module) => {

function _arrayLikeToArray(r, a) {
  (null == a || a > r.length) && (a = r.length);
  for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e];
  return n;
}
module.exports = _arrayLikeToArray, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/arrayWithHoles.js":
/*!****************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/arrayWithHoles.js ***!
  \****************************************************************/
/***/ ((module) => {

function _arrayWithHoles(r) {
  if (Array.isArray(r)) return r;
}
module.exports = _arrayWithHoles, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/arrayWithoutHoles.js":
/*!*******************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/arrayWithoutHoles.js ***!
  \*******************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray.js */ "../node_modules/@babel/runtime/helpers/arrayLikeToArray.js");
function _arrayWithoutHoles(r) {
  if (Array.isArray(r)) return arrayLikeToArray(r);
}
module.exports = _arrayWithoutHoles, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/defineProperty.js":
/*!****************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/defineProperty.js ***!
  \****************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var toPropertyKey = __webpack_require__(/*! ./toPropertyKey.js */ "../node_modules/@babel/runtime/helpers/toPropertyKey.js");
function _defineProperty(e, r, t) {
  return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}
module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/interopRequireDefault.js":
/*!***********************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/interopRequireDefault.js ***!
  \***********************************************************************/
/***/ ((module) => {

function _interopRequireDefault(e) {
  return e && e.__esModule ? e : {
    "default": e
  };
}
module.exports = _interopRequireDefault, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/iterableToArray.js":
/*!*****************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/iterableToArray.js ***!
  \*****************************************************************/
/***/ ((module) => {

function _iterableToArray(r) {
  if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r);
}
module.exports = _iterableToArray, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/iterableToArrayLimit.js":
/*!**********************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/iterableToArrayLimit.js ***!
  \**********************************************************************/
/***/ ((module) => {

function _iterableToArrayLimit(r, l) {
  var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"];
  if (null != t) {
    var e,
      n,
      i,
      u,
      a = [],
      f = !0,
      o = !1;
    try {
      if (i = (t = t.call(r)).next, 0 === l) {
        if (Object(t) !== t) return;
        f = !1;
      } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0);
    } catch (r) {
      o = !0, n = r;
    } finally {
      try {
        if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return;
      } finally {
        if (o) throw n;
      }
    }
    return a;
  }
}
module.exports = _iterableToArrayLimit, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/nonIterableRest.js":
/*!*****************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/nonIterableRest.js ***!
  \*****************************************************************/
/***/ ((module) => {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
module.exports = _nonIterableRest, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/nonIterableSpread.js":
/*!*******************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/nonIterableSpread.js ***!
  \*******************************************************************/
/***/ ((module) => {

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
module.exports = _nonIterableSpread, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/slicedToArray.js":
/*!***************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/slicedToArray.js ***!
  \***************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var arrayWithHoles = __webpack_require__(/*! ./arrayWithHoles.js */ "../node_modules/@babel/runtime/helpers/arrayWithHoles.js");
var iterableToArrayLimit = __webpack_require__(/*! ./iterableToArrayLimit.js */ "../node_modules/@babel/runtime/helpers/iterableToArrayLimit.js");
var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray.js */ "../node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");
var nonIterableRest = __webpack_require__(/*! ./nonIterableRest.js */ "../node_modules/@babel/runtime/helpers/nonIterableRest.js");
function _slicedToArray(r, e) {
  return arrayWithHoles(r) || iterableToArrayLimit(r, e) || unsupportedIterableToArray(r, e) || nonIterableRest();
}
module.exports = _slicedToArray, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/toConsumableArray.js":
/*!*******************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/toConsumableArray.js ***!
  \*******************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var arrayWithoutHoles = __webpack_require__(/*! ./arrayWithoutHoles.js */ "../node_modules/@babel/runtime/helpers/arrayWithoutHoles.js");
var iterableToArray = __webpack_require__(/*! ./iterableToArray.js */ "../node_modules/@babel/runtime/helpers/iterableToArray.js");
var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray.js */ "../node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");
var nonIterableSpread = __webpack_require__(/*! ./nonIterableSpread.js */ "../node_modules/@babel/runtime/helpers/nonIterableSpread.js");
function _toConsumableArray(r) {
  return arrayWithoutHoles(r) || iterableToArray(r) || unsupportedIterableToArray(r) || nonIterableSpread();
}
module.exports = _toConsumableArray, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/toPrimitive.js":
/*!*************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/toPrimitive.js ***!
  \*************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var _typeof = (__webpack_require__(/*! ./typeof.js */ "../node_modules/@babel/runtime/helpers/typeof.js")["default"]);
function toPrimitive(t, r) {
  if ("object" != _typeof(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != _typeof(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}
module.exports = toPrimitive, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/toPropertyKey.js":
/*!***************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/toPropertyKey.js ***!
  \***************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var _typeof = (__webpack_require__(/*! ./typeof.js */ "../node_modules/@babel/runtime/helpers/typeof.js")["default"]);
var toPrimitive = __webpack_require__(/*! ./toPrimitive.js */ "../node_modules/@babel/runtime/helpers/toPrimitive.js");
function toPropertyKey(t) {
  var i = toPrimitive(t, "string");
  return "symbol" == _typeof(i) ? i : i + "";
}
module.exports = toPropertyKey, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/typeof.js":
/*!********************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/typeof.js ***!
  \********************************************************/
/***/ ((module) => {

function _typeof(o) {
  "@babel/helpers - typeof";

  return module.exports = _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports, _typeof(o);
}
module.exports = _typeof, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js":
/*!****************************************************************************!*\
  !*** ../node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js ***!
  \****************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray.js */ "../node_modules/@babel/runtime/helpers/arrayLikeToArray.js");
function _unsupportedIterableToArray(r, a) {
  if (r) {
    if ("string" == typeof r) return arrayLikeToArray(r, a);
    var t = {}.toString.call(r).slice(8, -1);
    return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? arrayLikeToArray(r, a) : void 0;
  }
}
module.exports = _unsupportedIterableToArray, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!****************************************************************!*\
  !*** ../modules/interactions/assets/js/editor-interactions.js ***!
  \****************************************************************/


var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ "../node_modules/@babel/runtime/helpers/interopRequireDefault.js");
var _toConsumableArray2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "../node_modules/@babel/runtime/helpers/toConsumableArray.js"));
var _defineProperty2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "../node_modules/@babel/runtime/helpers/defineProperty.js"));
var _interactionsUtils = __webpack_require__(/*! ./interactions-utils.js */ "../modules/interactions/assets/js/interactions-utils.js");
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0, _defineProperty2.default)(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function applyAnimation(element, animConfig, animateFunc) {
  var keyframes = (0, _interactionsUtils.getKeyframes)(animConfig.effect, animConfig.type, animConfig.direction, element);
  var options = {
    duration: animConfig.duration / 1000,
    delay: animConfig.delay / 1000,
    easing: _interactionsUtils.config.easing
  };
  var initialKeyframes = {};
  Object.keys(keyframes).forEach(function (key) {
    initialKeyframes[key] = keyframes[key][0];
  });
  // WHY - Transition can be set on elements but once it sets it destroys all animations, so we basically put it aside.
  var transition = element.style.transition;
  element.style.transition = 'none';
  animateFunc(element, initialKeyframes, {
    duration: 0
  }).then(function () {
    animateFunc(element, keyframes, options).then(function () {
      if ('out' === animConfig.type) {
        var resetValues = {
          opacity: 1,
          scale: 1,
          x: 0,
          y: 0
        };
        var resetKeyframes = {};
        Object.keys(keyframes).forEach(function (key) {
          resetKeyframes[key] = resetValues[key];
        });
        element.style.transition = transition;
        animateFunc(element, resetKeyframes, {
          duration: 0
        });
      }
    });
  });
}
function getInteractionsData() {
  var scriptTag = document.querySelector('script[data-e-interactions="true"]');
  if (!scriptTag) {
    return [];
  }
  try {
    return JSON.parse(scriptTag.textContent || '[]');
  } catch (_unused) {
    return [];
  }
}
function findElementByInteractionId(interactionId) {
  return document.querySelector('[data-interaction-id="' + interactionId + '"]');
}
function applyInteractionsToElement(element, interactionsData) {
  var _window$Motion, _parsedData;
  var animateFunc = 'undefined' !== typeof animate ? animate : (_window$Motion = window.Motion) === null || _window$Motion === void 0 ? void 0 : _window$Motion.animate;
  if (!animateFunc) {
    return;
  }
  var parsedData;
  if ('string' === typeof interactionsData) {
    try {
      parsedData = JSON.parse(interactionsData);
    } catch (error) {
      return;
    }
  } else {
    parsedData = interactionsData;
  }
  var interactions = Object.values((_parsedData = parsedData) === null || _parsedData === void 0 ? void 0 : _parsedData.items);
  interactions.forEach(function (interaction) {
    var _interaction$animatio;
    var animationName = 'string' === typeof interaction ? interaction : interaction === null || interaction === void 0 || (_interaction$animatio = interaction.animation) === null || _interaction$animatio === void 0 ? void 0 : _interaction$animatio.animation_id;
    var animConfig = animationName && (0, _interactionsUtils.parseAnimationName)(animationName);
    if (animConfig) {
      applyAnimation(element, animConfig, animateFunc);
    }
  });
}
var previousInteractionsData = [];
function handleInteractionsUpdate() {
  var currentInteractionsData = getInteractionsData();
  var changedItems = currentInteractionsData.filter(function (currentItem) {
    var previousItem = previousInteractionsData.find(function (prev) {
      return prev.dataId === currentItem.dataId;
    });
    return !previousItem || previousItem.interactions !== currentItem.interactions;
  });
  changedItems.forEach(function (item) {
    var _previousInteractions, _item$interactions, _item$interactions2, _prevInteractions$ite;
    var element = findElementByInteractionId(item.dataId);
    var prevInteractions = (_previousInteractions = previousInteractionsData.find(function (prev) {
      return prev.dataId === item.dataId;
    })) === null || _previousInteractions === void 0 ? void 0 : _previousInteractions.interactions;
    if (element && ((_item$interactions = item.interactions) === null || _item$interactions === void 0 || (_item$interactions = _item$interactions.items) === null || _item$interactions === void 0 ? void 0 : _item$interactions.length) > 0 && ((_item$interactions2 = item.interactions) === null || _item$interactions2 === void 0 || (_item$interactions2 = _item$interactions2.items) === null || _item$interactions2 === void 0 ? void 0 : _item$interactions2.length) === (prevInteractions === null || prevInteractions === void 0 || (_prevInteractions$ite = prevInteractions.items) === null || _prevInteractions$ite === void 0 ? void 0 : _prevInteractions$ite.length)) {
      var interactionsToApply = _objectSpread(_objectSpread({}, item.interactions), {}, {
        items: (0, _toConsumableArray2.default)(item.interactions.items).filter(function (interaction, index) {
          var _prevInteractions$ite2;
          return (prevInteractions === null || prevInteractions === void 0 || (_prevInteractions$ite2 = prevInteractions.items[index]) === null || _prevInteractions$ite2 === void 0 || (_prevInteractions$ite2 = _prevInteractions$ite2.animation) === null || _prevInteractions$ite2 === void 0 ? void 0 : _prevInteractions$ite2.animation_id) !== interaction.animation.animation_id;
        })
      });
      applyInteractionsToElement(element, interactionsToApply);
    }
  });
  previousInteractionsData = currentInteractionsData;
}
function initEditorInteractionsHandler() {
  var _window$Motion2;
  if ('undefined' === typeof animate && !((_window$Motion2 = window.Motion) !== null && _window$Motion2 !== void 0 && _window$Motion2.animate)) {
    setTimeout(initEditorInteractionsHandler, 100);
    return;
  }
  var head = document.head;
  var scriptTag = null;
  var observer = null;
  function setupObserver(tag) {
    if (observer) {
      observer.disconnect();
    }
    observer = new MutationObserver(function () {
      handleInteractionsUpdate();
    });
    observer.observe(tag, {
      childList: true,
      characterData: true,
      subtree: true
    });
    handleInteractionsUpdate();
    registerWindowEvents();
  }
  var headObserver = new MutationObserver(function () {
    var foundScriptTag = document.querySelector('script[data-e-interactions="true"]');
    if (foundScriptTag && foundScriptTag !== scriptTag) {
      scriptTag = foundScriptTag;
      setupObserver(scriptTag);
      headObserver.disconnect();
    }
  });
  headObserver.observe(head, {
    childList: true,
    subtree: true
  });
  scriptTag = document.querySelector('script[data-e-interactions="true"]');
  if (scriptTag) {
    setupObserver(scriptTag);
    headObserver.disconnect();
  }
}
function registerWindowEvents() {
  window.top.addEventListener('atomic/play_interactions', handlePlayInteractions);
}
function handlePlayInteractions(event) {
  var _event$detail = event.detail,
    elementId = _event$detail.elementId,
    animationId = _event$detail.animationId;
  var interactionsData = getInteractionsData();
  var item = interactionsData.find(function (elementItemData) {
    return elementItemData.dataId === elementId;
  });
  if (!item) {
    return;
  }
  var element = findElementByInteractionId(elementId);
  if (element) {
    var interactionsCopy = _objectSpread(_objectSpread({}, item.interactions), {}, {
      items: (0, _toConsumableArray2.default)(item.interactions.items)
    });
    interactionsCopy.items = interactionsCopy.items.filter(function (interactionItem) {
      return interactionItem.animation.animation_id === animationId;
    });
    applyInteractionsToElement(element, JSON.stringify(interactionsCopy));
  }
}
if ('loading' === document.readyState) {
  document.addEventListener('DOMContentLoaded', initEditorInteractionsHandler);
} else {
  initEditorInteractionsHandler();
}
})();

/******/ })()
;
//# sourceMappingURL=editor-interactions.js.map
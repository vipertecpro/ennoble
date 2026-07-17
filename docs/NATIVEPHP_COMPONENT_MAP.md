# Ennoble NativePHP v4 Component Map

## Audit Context

This inventory was verified against the NativePHP Mobile v4 documentation and the packages currently installed in `vendor/`.

| Package | Installed version | Source reference | Current build state |
| --- | --- | --- | --- |
| `nativephp/mobile` | `dev-element` | `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d` | Core package discovered |
| `nativephp/native-ui` | `dev-feat/webview-element` | `ce3d8b760c89dd08e14baad8b05afd82494d3c46` | Installed but not registered |
| `nativephp/mobile-camera` | `1.0.3` | `b01139ea47029b6eae695d1a16c41e00f265fd54` | Installed but not registered; no v1 use identified |

`php artisan native:plugin:list` reports that `App\Providers\NativeServiceProvider` has not been published. Consequently, installed Native UI renderers are not currently included in native builds. `php artisan native:plugin:validate` also reports that the installed Native UI manifest lacks `ios.min_version`.

The availability labels in this document mean:

- **Core source present:** PHP element/component or framework API exists in `nativephp/mobile`.
- **UI source present, registration blocked:** PHP implementation and native renderers exist in `nativephp/native-ui`, but the plugin is not registered for builds.
- **Verified framework API:** non-element capability exists in v4 source and documentation.
- **Excluded:** verified capability that must not be used as Ennoble's primary interface.

## Shared Element Behavior

All elements use the shared layout/styling and accessibility systems documented in [Layout and Styling](https://nativephp.com/docs/mobile/4/edge-components/layout) and [Accessibility](https://nativephp.com/docs/mobile/4/digging-deeper/accessibility).

Important shared capabilities include supported sizing, spacing, flex alignment, theme-aware utility classes, `a11y-label`, `a11y-hint`, and interaction attributes such as `@press`, `@longPress`, and `@doubleTap` where accepted. Required properties are validated when the tree renders.

Do not infer that arbitrary CSS, browser DOM behavior, or undocumented Tailwind utilities are supported. Confirm implementation-specific classes in `Native\Mobile\Edge\TailwindParser`.

## Layout and Content

| Exact tag/API | Documentation | Confirmed purpose and important interface | Ennoble use | Platform notes and limits | Installed availability |
| --- | --- | --- | --- | --- | --- |
| `<native:column>` | [Column](https://nativephp.com/docs/mobile/4/edge-components/column) | Vertical flex container; shared layout props; child elements | Screen stacks, cards, game instructions | Native layout behavior can differ from browser flexbox | Core PHP source present; renderers in unregistered UI plugin |
| `<native:row>` | [Row](https://nativephp.com/docs/mobile/4/edge-components/row) | Horizontal flex container; alignment, spacing, children | Metric rows, compact actions | Verify wrapping and large-text behavior on both platforms | Core PHP source present; renderers in unregistered UI plugin |
| `<native:stack>` | [Stack](https://nativephp.com/docs/mobile/4/edge-components/stack) | Layers children | Game overlays and decorative geometry | Installed docs note absolute children should use a row/column parent instead | Core PHP source present; renderers in unregistered UI plugin |
| `<native:scroll-view>` | [Scroll View](https://nativephp.com/docs/mobile/4/edge-components/scroll-view) | Scrollable child content; direction and scroll behavior | Profile, results, About, long explanations | Scroll physics are platform native; avoid nesting scroll containers | Core PHP source present; renderers in unregistered UI plugin |
| `<native:spacer>` | [Spacer](https://nativephp.com/docs/mobile/4/edge-components/spacer) | Flexible or sized spacing | Layout distribution | Prefer ordinary gap/padding unless flexible space is intended | Core PHP source present; renderers in unregistered UI plugin |
| `<native:text>` | [Text](https://nativephp.com/docs/mobile/4/edge-components/text) | Native text, typography utilities, bundled `font`, accessibility | All labels, instructions, metrics, explanations | Text scales with platform settings; iOS tightening of line height is limited | Core PHP source present; renderers in unregistered UI plugin |
| `<native:image>` | [Image](https://nativephp.com/docs/mobile/4/edge-components/image) | Local/bundled image display; `alt` for meaningful images | Original illustrations and local assets | No remote images in Ennoble; verify fit/cropping by platform | Core PHP source present; renderers in unregistered UI plugin |
| `<native:icon>` | [Icon](https://nativephp.com/docs/mobile/4/edge-components/icon) and [Icons](https://nativephp.com/docs/mobile/4/edge-components/icons) | SF Symbols/Material icons, typed platform enum overrides, optional accessibility label | Navigation and actions | Symbol names differ; use generated `Ios`, `Android`, and `AndroidOutlined` enums | Core PHP element and UI implementation present; registration blocked |
| `<native:divider>` | [Divider](https://nativephp.com/docs/mobile/4/edge-components/divider) | Visual separation | Settings and summary grouping | Must not be the only hierarchy cue | Core PHP source present; renderers in unregistered UI plugin |
| `<native:badge>` | [Badge](https://nativephp.com/docs/mobile/4/edge-components/badge) | Compact status/count marker | Coming Soon and achievement state | Avoid presenting essential meaning only through color | UI source present, registration blocked |
| `<native:progress-bar>` | [Progress Bar](https://nativephp.com/docs/mobile/4/edge-components/progress-bar) | Determinate/indeterminate progress presentation | Workout and session progress | Use activity indicator instead when progress is unknowable | UI source present, registration blocked |
| `<native:activity-indicator>` | [Activity Indicator](https://nativephp.com/docs/mobile/4/edge-components/activity-indicator) | Indeterminate native busy state | Perceptible local generation or persistence | Most local work should be fast; do not flash loaders unnecessarily | UI source present, registration blocked |

## Interaction and Forms

| Exact tag/API | Documentation | Confirmed purpose and important interface | Ennoble use | Platform notes and limits | Installed availability |
| --- | --- | --- | --- | --- | --- |
| `<native:pressable>` | [Pressable](https://nativephp.com/docs/mobile/4/edge-components/pressable) | Makes composed content interactive; press events and accessibility | Game targets and custom cards | Press target needs visible state and accessible label | Core PHP source present; renderers in unregistered UI plugin |
| `<native:button>` | [Button](https://nativephp.com/docs/mobile/4/edge-components/button) | Native button; semantic variants, label/icon, `@press`, disabled state | Primary and secondary actions | Theme controls appearance; per-instance color overrides may be ignored | UI source present, registration blocked |
| `<native:button-group>` | [Button Group](https://nativephp.com/docs/mobile/4/edge-components/button-group) | Mutually related actions; `native:model`, `@change` | Difficulty or goal choice when compact | Confirm segmented appearance on both platforms | UI source present, registration blocked |
| `<native:chip>` | [Chip](https://nativephp.com/docs/mobile/4/edge-components/chip) | Compact selectable/filter action; model/change behavior | Skill and difficulty filters | Icon-only chips require an accessibility label | UI source present, registration blocked |
| `<native:toggle>` | [Toggle](https://nativephp.com/docs/mobile/4/edge-components/toggle) | Boolean setting; `native:model`, `@change`, label | Sound, haptic, reduced motion | Uses platform switch conventions | UI source present, registration blocked |
| `<native:checkbox>` | [Checkbox](https://nativephp.com/docs/mobile/4/edge-components/checkbox) | Independent boolean selection; model/change behavior | Only when a checkbox is semantically clearer than a toggle | Native visual conventions differ | UI source present, registration blocked |
| `<native:radio-group>` / `<native:radio>` | [Radio Group](https://nativephp.com/docs/mobile/4/edge-components/radio-group) | Single selection among labeled options; model/change behavior | Theme, training goal, or detailed difficulty choice | Keep options short enough for scalable text | UI source present, registration blocked |
| `<native:select>` | [Select](https://nativephp.com/docs/mobile/4/edge-components/select) | Native selection control; model/change behavior | Settings with several choices | Picker presentation differs by platform | UI source present, registration blocked |
| `<native:slider>` | [Slider](https://nativephp.com/docs/mobile/4/edge-components/slider) | Bounded numeric selection; model/change behavior | Only for a genuinely continuous preference | Do not use for discrete difficulty labels | UI source present, registration blocked |
| `<native:bare-text-input>` | [Text Input](https://nativephp.com/docs/mobile/4/edge-components/text-input) | Unadorned native text entry; `native:model`, change/submit events | Display-name editing inside a custom row | Must supply a visible/accessibility label in surrounding UI | UI source present, registration blocked |
| `<native:outlined-text-input>` | [Text Input](https://nativephp.com/docs/mobile/4/edge-components/text-input) | Outlined labeled text entry; model/change/submit behavior | Display-name editing | Keyboard and field styling are platform native | UI source present, registration blocked |
| `<native:filled-text-input>` | [Text Input](https://nativephp.com/docs/mobile/4/edge-components/text-input) | Filled labeled text entry; model/change/submit behavior | Alternative profile form style | Choose one field style consistently | UI source present, registration blocked |

## Navigation, Collections, and Overlays

| Exact tag/API | Documentation | Confirmed purpose and important interface | Ennoble use | Platform notes and limits | Installed availability |
| --- | --- | --- | --- | --- | --- |
| `Route::native()` / component navigation | [Routing](https://nativephp.com/docs/mobile/4/the-basics/routing) | Registers native screens; `navigate`, `replace`, `back`, route params/data, transitions | All mobile destinations and focused game flows | Native back behavior differs on Android; transitions require source verification | Verified framework API |
| `NativeLayout`, `NavBar`, `TabBar`, `Tab` | [Layouts](https://nativephp.com/docs/mobile/4/the-basics/layouts) | Shared native chrome, safe-area handling, up to five tabs | Today, Games, Progress, Profile shell | Layout chrome applies safe areas; do not duplicate them in screens | Verified framework API |
| `<native:top-bar>` | [Top Bar](https://nativephp.com/docs/mobile/4/edge-components/top-bar) | Inline native top bar with actions | Only if a layout bar is unsuitable | Prefer one chrome model; platform title/action placement differs | Core PHP source present; renderers in unregistered UI plugin |
| `<native:bottom-nav>` / `<native:bottom-nav-item>` | [Bottom Navigation](https://nativephp.com/docs/mobile/4/edge-components/bottom-navigation) | Inline bottom navigation and destination items | Four primary sections if layout tabs are not used | Item taps use replace-style behavior; avoid duplicate layout tab bars | Core PHP source present; renderers in unregistered UI plugin |
| `<native:tab-row>` / `<native:tab>` | [Tab Row](https://nativephp.com/docs/mobile/4/edge-components/tab-row) | In-screen tab selection; `native:model`, `@change` | Progress subviews if needed | Not a replacement for app-level navigation | UI source present, registration blocked |
| `<native:list>` / `<native:list-item>` / `<native:list-section>` | [List](https://nativephp.com/docs/mobile/4/edge-components/list) | Native structured lists, item content/actions, refresh/end events where configured | Settings, achievements, history | Label trailing icon actions separately; list styling differs by platform | UI source present, registration blocked |
| `<native:virtual-list>` | [Virtual List](https://nativephp.com/docs/mobile/4/edge-components/virtual-list) | Windowed rendering for large collections; item/window interface | Long challenge/history collections only if necessary | Prefer ordinary lists for small v1 datasets; special precompiler behavior | UI source present, registration blocked |
| `<native:lazy-grid>` | [List](https://nativephp.com/docs/mobile/4/edge-components/list) and installed source | Grid that lazily presents children | Games and achievement grid if supported by final layout | Installed Android renderer availability must be verified during registration/build | Core PHP source and manifest entry present; build unverified |
| `<native:carousel>` | [Carousel](https://nativephp.com/docs/mobile/4/edge-components/carousel) | Horizontally paged content | Optional Today highlights, not core navigation | Avoid hiding essential content off-screen | UI source present, registration blocked |
| `<native:modal>` | [Modal](https://nativephp.com/docs/mobile/4/edge-components/modal) | Modal content with dismissal event | Destructive confirmation or focused result detail | Follow platform dismissal conventions | UI source present, registration blocked |
| `<native:bottom-sheet>` | [Bottom Sheet](https://nativephp.com/docs/mobile/4/edge-components/bottom-sheet) | Native sheet with dismissal behavior | Coming Soon details and contextual explanations | Presentation height/drag behavior varies by platform | UI source present, registration blocked |
| Core `Dialog` facade | [Dialogs](https://nativephp.com/docs/mobile/4/the-basics/dialogs) | Native alerts and toasts; button result events | Simple confirmations or brief feedback | Use a sheet/modal when richer content is required | Verified core API |

## Drawing, Gestures, Haptics, and Motion

| Exact tag/API | Documentation | Confirmed purpose and important interface | Ennoble use | Platform notes and limits | Installed availability |
| --- | --- | --- | --- | --- | --- |
| `<native:canvas>` | [Canvas](https://nativephp.com/docs/mobile/4/edge-components/canvas) | Container for native drawing shapes | Signal Shift field after performance validation | Not a general-purpose game engine | Core PHP source present; renderers in unregistered UI plugin |
| `<native:circle>`, `<native:rect>`, `<native:line>` | [Shapes](https://nativephp.com/docs/mobile/4/edge-components/shapes) | Primitive native shapes with geometric/style props | Original targets, distractors, progress art | Verify exact sizing, color, and animation props before use | Core PHP source present; renderers in unregistered UI plugin |
| `<native:gesture-area>` | [Gesture Area](https://nativephp.com/docs/mobile/4/edge-components/gesture-area) | Swipe/pinch/gesture events and shared-value integration | Reorder interaction only if simple press alternatives are insufficient | Gesture semantics and native-thread animation require both-platform tests | Core PHP source present; renderers in unregistered UI plugin |
| `SharedValue` and verified animation props | [Gestures and Animation](https://nativephp.com/docs/mobile/4/digging-deeper/gestures-and-animation) | Native-thread interaction/motion without PHP work each frame | Restrained target/feedback motion | v4 is pre-release; verify installed wire and renderer support per effect | Verified source capability; build unverified |
| `Device::vibrate()` | [Device](https://nativephp.com/docs/mobile/4/the-basics/device) | Core vibration call returning success/failure | Optional correct, incorrect, and completion feedback | It is vibration, not a promise of named haptic patterns | Verified core API |

## Accessibility and Testing Utilities

| Exact API | Documentation | Confirmed purpose | Ennoble use | Limitations | Availability |
| --- | --- | --- | --- | --- | --- |
| `a11y-label`, `a11y-hint`, image `alt` | [Accessibility](https://nativephp.com/docs/mobile/4/digging-deeper/accessibility) | Screen-reader metadata shared by elements | Label controls, meaningful images, and custom game targets | Does not replace manual VoiceOver/TalkBack verification | Verified framework capability |
| `Native::test(Component::class)` | [Testing Introduction](https://nativephp.com/docs/mobile/4/testing/introduction) | In-process NativeComponent harness | Rendering and state tests | No simulator/device/native renderer is launched | Verified installed testing API |
| `Native::visit('/route')` and navigation assertions | [Navigation Tests](https://nativephp.com/docs/mobile/4/testing/navigation) | Route resolution, navigation intents, following flows, chrome assertions | Shell and game-flow tests | Requires actual native routes/components | Verified installed testing API |
| Interaction helpers | [Interactions](https://nativephp.com/docs/mobile/4/testing/interactions) | Tap, press, input, toggle, select, call, poll, and back actions | User-interaction tests | Targets need visible text or stable refs | Verified installed testing API |
| `Native::fakeBridge()` / `emitNative()` | [Native Events](https://nativephp.com/docs/mobile/4/testing/native-events) | Scripts bridge responses and delivers native events | Haptic/native-call tests without a device | Fakes cannot prove platform integration | Verified installed testing API |
| `assertAccessible()` / `accessibilityViolations()` | [Accessibility Tests](https://nativephp.com/docs/mobile/4/testing/accessibility) | Audits common missing labels and inaccessible controls | Required screen-level automated check | Does not evaluate every contrast, reading-order, or motor-access issue | Verified installed testing API |
| Platform rendering and wire-tree assertions | [Advanced Testing](https://nativephp.com/docs/mobile/4/testing/advanced) | iOS/Android variants, element assertions, snapshots, render counts | Cross-platform conditional UI and regression tests | Wire snapshots are not visual screenshots | Verified installed testing API |

## Explicitly Excluded Primary Interface

`<native:web-view>` is documented at [Web View](https://nativephp.com/docs/mobile/4/edge-components/web-view) and its source exists in the installed Native UI development branch. It is **excluded** as Ennoble's application shell or primary screen technology. It may be considered only for a future approved requirement that genuinely embeds web content.

## Source Locations Consulted

- `vendor/nativephp/mobile/src/Edge/`
- `vendor/nativephp/mobile/src/Testing/`
- `vendor/nativephp/mobile/src/NativeServiceProvider.php`
- `vendor/nativephp/native-ui/nativephp.json`
- `vendor/nativephp/native-ui/src/Elements/`
- `vendor/nativephp/native-ui/src/Components/`
- `vendor/nativephp/native-ui/resources/android/`
- `vendor/nativephp/native-ui/resources/ios/`
- `vendor/nativephp/native-ui/tests/`

No component in this map should be used until its current documentation page and installed implementation are rechecked at the time of implementation.

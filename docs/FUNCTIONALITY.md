# Component Functionality

[⬅️ Back to main description](../README.md) | [ℹ️ Additional information](ADDITIONAL.md)

## 4. Post Info Block

### 4.1. Block Structure
The complete post info block displays:

- **Post Indices** - as links to the forum
- **Main Information Line** - author, subject, tree level, date and time
- **Reminder Lines** - beginning of the previous message

### 4.2. Reminder Lines Format
- Optimal length: 50-150 characters
- Format of links and images in reminder lines:

**Links:**
- With text: 🔗"Link text"🔗
- Without text: 🔗url🔗 (shortened to 40 characters)

**Images:**
- With non-empty alt text: 🖼️alt_text🖼️
- Without alt text: 🖼️filename.extension🖼️, for example, 🖼️Image1.png🖼️

> If a link or image exceeds the limit, information about them is still displayed in full.

## 5. Parsing

Kunena BBCode is converted to Joomla HTML using the [chriskonnertz/bbcode](https://github.com/chriskonnertz/bbcode) parser. Huge thanks to the developer.

**Additional processing:**
- Shortening "bare" URLs
- Processing links and images in reminder lines
- Fixing problematic character sequences (e.g., `[br /`)

## 6. Language Files

**Supported languages:** English, German, Russian. Only the Russian text is authentic. English and German translations may require improvement. Code comments are made predominantly in Russian (you can contact the developer for explanations).

**Important localization constants:**
```
COM_KUNENATOPIC2ARTICLE_INFORMATION_SIGN_LENGTH=
COM_KUNENATOPIC2ARTICLE_WARNING_SIGN_LENGTH=
```
They contain the lengths of service lines specified in section 2.3. When adding new languages, it is recommended to recalculate the lengths in these lines (for correct operation of the **Maximum Article Size** parameter).

## 7. Post Relations in Kunena

### 7.1. Tree Structure (a bit of theory)
Currently, posts in a topic are displayed in chronological order.
However, Kunena also has deeper connections between posts:
  - a new post can be a reply in the topic
  - a new post can be a reply to a specific post
Thus, any topic has a tree structure of posts, reflecting the logical connections between them. (For forums with serious discussions and a large number of posts, the tree structure provides great convenience).

### 7.2. Transfer Schemes
The component supports two schemes for transferring posts to articles (determined by the "Post Transfer Scheme" parameter, see section 1.2):
- sequential (Flat) - posts are transferred in chronological order
- threaded (Tree) - the logical structure of the discussion is preserved

### 7.3. Handling Problematic Posts

7.3.1. A topic may contain temporarily hidden (hold = 2) posts. It may also have previously had posts that were deleted by the time the component runs.

7.3.2. In the sequential scheme, temporarily hidden posts are not transferred, deleted ones naturally are not either, and the rest (published) are transferred to articles.

7.3.3. In the threaded scheme, temporarily hidden posts break, and deleted posts cut off branches extending from them. To avoid losing posts from these branches, the following tree repair rules are adopted:
- Temporarily hidden posts contain information about their parents (parent field). An existing ancestor of a temporarily hidden post is assigned as the parent of its children.
- The situation is different with completely deleted posts. Information about their parents disappears along with them. To save cut-off branches, the component assigns the first post of the topic as the parent of the first posts of these branches.

7.3.4. If post index links are enabled in the parameters, the index of a temporarily hidden post is displayed as regular text (not a link). The index of a completely deleted post is replaced with the index of the first post of the topic, according to section 7.3.3.

---

*[⬅️ Back to main description](../README.md) | [➡️ Additional information](ADDITIONAL.md)*

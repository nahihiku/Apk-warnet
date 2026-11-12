#pragma once
#include <windows.h>
#include <winhttp.h>
#include <string>
#include <vector>

struct HttpResponse {
    bool ok = false;
    int status_code = 0;
    std::string body;
};

inline std::wstring to_wstring_utf8(const std::string& str) {
    if (str.empty()) return std::wstring();
    int sz = MultiByteToWideChar(CP_UTF8, 0, str.c_str(), (int)str.size(), nullptr, 0);
    std::wstring res(sz, 0);
    MultiByteToWideChar(CP_UTF8, 0, str.c_str(), (int)str.size(), &res[0], sz);
    return res;
}

inline HttpResponse http_request(
    const std::string& host,
    int port,
    const std::string& method,
    const std::string& path,
    const std::string& json_body = ""
) {
    HttpResponse res;
    HINTERNET hSession = WinHttpOpen(
        L"UkikuraNativeClient/1.0",
        WINHTTP_ACCESS_TYPE_NO_PROXY,
        WINHTTP_NO_PROXY_NAME,
        WINHTTP_NO_PROXY_BYPASS,
        0
    );
    if (!hSession) return res;

    // Fast LAN timeouts (ms)
    WinHttpSetTimeouts(hSession, 1500, 1500, 2000, 2000);

    std::wstring wHost = to_wstring_utf8(host);
    HINTERNET hConnect = WinHttpConnect(hSession, wHost.c_str(), (INTERNET_PORT)port, 0);
    if (!hConnect) {
        WinHttpCloseHandle(hSession);
        return res;
    }

    std::wstring wMethod = to_wstring_utf8(method);
    std::wstring wPath = to_wstring_utf8(path);
    HINTERNET hRequest = WinHttpOpenRequest(
        hConnect,
        wMethod.c_str(),
        wPath.c_str(),
        nullptr,
        WINHTTP_NO_REFERER,
        WINHTTP_DEFAULT_ACCEPT_TYPES,
        0
    );
    if (!hRequest) {
        WinHttpCloseHandle(hConnect);
        WinHttpCloseHandle(hSession);
        return res;
    }

    LPCWSTR headers = L"Content-Type: application/json\r\nAccept: application/json\r\n";
    BOOL bResults = WinHttpSendRequest(
        hRequest,
        headers,
        -1L,
        json_body.empty() ? WINHTTP_NO_REQUEST_DATA : (LPVOID)json_body.c_str(),
        (DWORD)json_body.size(),
        (DWORD)json_body.size(),
        0
    );

    if (bResults) {
        bResults = WinHttpReceiveResponse(hRequest, nullptr);
    }

    if (bResults) {
        DWORD dwStatusCode = 0;
        DWORD dwSize = sizeof(dwStatusCode);
        WinHttpQueryHeaders(
            hRequest,
            WINHTTP_QUERY_STATUS_CODE | WINHTTP_QUERY_FLAG_NUMBER,
            WINHTTP_HEADER_NAME_BY_INDEX,
            &dwStatusCode,
            &dwSize,
            WINHTTP_NO_HEADER_INDEX
        );
        res.status_code = (int)dwStatusCode;
        res.ok = (dwStatusCode >= 200 && dwStatusCode < 300);

        DWORD dwAvailable = 0;
        do {
            dwAvailable = 0;
            if (!WinHttpQueryDataAvailable(hRequest, &dwAvailable)) break;
            if (dwAvailable == 0) break;

            std::vector<char> buffer(dwAvailable + 1, 0);
            DWORD dwDownloaded = 0;
            if (WinHttpReadData(hRequest, buffer.data(), dwAvailable, &dwDownloaded)) {
                res.body.append(buffer.data(), dwDownloaded);
            }
        } while (dwAvailable > 0);
    }

    WinHttpCloseHandle(hRequest);
    WinHttpCloseHandle(hConnect);
    WinHttpCloseHandle(hSession);
    return res;
}
